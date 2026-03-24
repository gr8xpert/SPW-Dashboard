<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Inquiry;
use App\Services\WidgetSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WidgetInquiryController extends Controller
{
    public function __construct(
        protected WidgetSubscriptionService $subscriptionService
    ) {}

    /**
     * POST /api/v1/widget/capture-inquiry
     *
     * Captures a property inquiry from the widget, validates reCAPTCHA,
     * sends email to owner, and creates/updates a contact in SmartMailer.
     */
    public function captureInquiry(Request $request): JsonResponse
    {
        $request->validate([
            'domain'         => 'required|string',
            'name'           => 'required|string|max:200',
            'email'          => 'required|email|max:320',
            'phone'          => 'nullable|string|max:50',
            'message'        => 'nullable|string|max:5000',
            'property_ref'   => 'nullable|string|max:100',
            'property_title' => 'nullable|string|max:500',
            'property_url'   => 'nullable|url|max:1000',
            'property_price' => 'nullable|string|max:100',
            'recaptchaToken' => 'nullable|string',
        ]);

        $client = $this->subscriptionService->findClientByDomain($request->domain);
        if (!$client) {
            return response()->json(['success' => false, 'error' => 'Unknown domain'], 404);
        }

        // Verify reCAPTCHA if configured
        $widgetConfig = $client->widget_config ?? [];
        $recaptchaSecretKey = $widgetConfig['recaptchaSecretKey'] ?? '';
        $recaptchaSiteKey = $widgetConfig['recaptchaSiteKey'] ?? '';

        if (!empty($recaptchaSecretKey) && !empty($recaptchaSiteKey)) {
            $recaptchaToken = $request->input('recaptchaToken', '');

            if (empty($recaptchaToken)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Please complete the reCAPTCHA verification.'
                ], 400);
            }

            // Verify with Google
            $verifyResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $recaptchaSecretKey,
                'response' => $recaptchaToken,
                'remoteip' => $request->ip(),
            ]);

            $verifyData = $verifyResponse->json();

            if (!($verifyData['success'] ?? false)) {
                Log::warning("reCAPTCHA verification failed for {$request->domain}", [
                    'error_codes' => $verifyData['error-codes'] ?? [],
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'reCAPTCHA verification failed. Please try again.'
                ], 400);
            }
        }

        // Debug logging
        Log::info("Processing inquiry for domain: {$request->domain}", [
            'client_id' => $client->id,
            'owner_email' => $client->owner_email ?? 'NOT SET',
            'user_email' => $request->email,
        ]);

        // Send email to owner
        $emailSent = $this->sendInquiryEmail($client, $request);
        if (!$emailSent) {
            Log::error("Failed to send inquiry email for {$request->domain}", [
                'owner_email' => $client->owner_email ?? 'NOT SET',
            ]);
        } else {
            Log::info("Owner inquiry email sent successfully for {$request->domain}");
        }

        // Send confirmation email to user
        $confirmationSent = $this->sendUserConfirmationEmail($client, $request);
        if (!$confirmationSent) {
            Log::warning("Failed to send confirmation email to user for {$request->domain}");
        } else {
            Log::info("User confirmation email sent successfully to {$request->email}");
        }

        // Parse name into first/last
        $nameParts = explode(' ', $request->name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        // Create or update contact (one per email for email campaigns)
        $contact = Contact::updateOrCreate(
            ['client_id' => $client->id, 'email' => $request->email],
            [
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'phone'         => $request->phone,
                'status'        => 'subscribed',
                'source'        => 'widget_inquiry',
                'tags'          => json_encode(array_filter([
                    'widget-inquiry',
                    $request->property_ref ? 'property-' . $request->property_ref : null,
                ])),
                'custom_fields' => json_encode([
                    'last_inquiry_property' => $request->property_ref,
                    'last_inquiry_title'    => $request->property_title,
                    'last_inquiry_message'  => $request->message,
                    'last_inquiry_date'     => now()->toISOString(),
                ]),
            ]
        );

        // Create inquiry record (one per submission for tracking)
        $inquiry = Inquiry::create([
            'client_id'       => $client->id,
            'contact_id'      => $contact->id,
            'name'            => $request->name,
            'email'           => $request->email,
            'phone'           => $request->phone,
            'message'         => $request->message,
            'property_ref'    => $request->property_ref,
            'property_title'  => $request->property_title,
            'property_url'    => $request->property_url,
            'property_price'  => $request->property_price,
            'status'          => 'new',
            'source'          => 'widget',
            'ip_address'      => $request->ip(),
            'user_agent'      => substr($request->userAgent() ?? '', 0, 500),
        ]);

        // Add to "Widget Inquiries" list (auto-create if needed)
        $list = ContactList::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Widget Inquiries'],
            ['type' => 'static', 'description' => 'Contacts captured from widget inquiry forms']
        );

        $contact->lists()->syncWithoutDetaching([
            $list->id => ['client_id' => $client->id]
        ]);

        return response()->json([
            'success'    => true,
            'inquiry_id' => $inquiry->id,
            'contact_id' => $contact->id,
            'email_sent' => $emailSent,
            'message'    => 'Your inquiry has been sent successfully!',
        ]);
    }

    /**
     * Send inquiry email to the property owner.
     */
    protected function sendInquiryEmail(Client $client, Request $request): bool
    {
        $ownerEmail = $client->owner_email;
        if (empty($ownerEmail)) {
            return false;
        }

        $widgetConfig = $client->widget_config ?? [];
        $branding = $widgetConfig['branding'] ?? [];
        $companyName = $branding['companyName'] ?? $client->company_name ?? 'Property Widget';
        $primaryColor = $branding['primaryColor'] ?? '#667eea';
        $emailHeaderColor = $branding['emailHeaderColor'] ?? $primaryColor;
        $logoUrl = $branding['logoUrl'] ?? '';
        $websiteUrl = $branding['websiteUrl'] ?? '';

        $name = $request->input('name');
        $email = $request->input('email');
        $phone = $request->input('phone', '');
        $message = $request->input('message', '');
        $propertyRef = $request->input('property_ref', '');
        $propertyTitle = $request->input('property_title', 'Property');
        $propertyUrl = $request->input('property_url', '');
        $propertyPrice = $request->input('property_price', '');

        $subject = "Property Inquiry: {$propertyTitle}" . ($propertyRef ? " (Ref: {$propertyRef})" : '');

        try {
            $emailHtml = $this->buildInquiryEmailHtml([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'propertyRef' => $propertyRef,
                'propertyTitle' => $propertyTitle,
                'propertyUrl' => $propertyUrl,
                'propertyPrice' => $propertyPrice,
                'companyName' => $companyName,
                'primaryColor' => $primaryColor,
                'emailHeaderColor' => $emailHeaderColor,
                'logoUrl' => $logoUrl,
                'websiteUrl' => $websiteUrl,
            ]);

            Mail::html($emailHtml, function ($mail) use ($ownerEmail, $email, $name, $subject) {
                $mail->to($ownerEmail)
                     ->replyTo($email, $name)
                     ->subject($subject);
            });

            Log::info("Inquiry sent for {$client->domain}", [
                'to' => $ownerEmail,
                'from' => $email,
                'property_ref' => $propertyRef,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send inquiry for {$client->domain}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send confirmation email to the user who submitted the inquiry.
     */
    protected function sendUserConfirmationEmail(Client $client, Request $request): bool
    {
        $userEmail = $request->input('email');
        if (empty($userEmail)) {
            return false;
        }

        $widgetConfig = $client->widget_config ?? [];
        $branding = $widgetConfig['branding'] ?? [];
        $companyName = $branding['companyName'] ?? $client->company_name ?? 'Property Widget';
        $primaryColor = $branding['primaryColor'] ?? '#667eea';
        $emailHeaderColor = $branding['emailHeaderColor'] ?? $primaryColor;
        $logoUrl = $branding['logoUrl'] ?? '';
        $websiteUrl = $branding['websiteUrl'] ?? '';

        $name = $request->input('name');
        $propertyTitle = $request->input('property_title', 'Property');
        $propertyRef = $request->input('property_ref', '');
        $propertyUrl = $request->input('property_url', '');

        $subject = "Your Inquiry: {$propertyTitle}" . ($propertyRef ? " (Ref: {$propertyRef})" : '');

        try {
            $emailHtml = $this->buildUserConfirmationEmailHtml([
                'name' => $name,
                'propertyTitle' => $propertyTitle,
                'propertyRef' => $propertyRef,
                'propertyUrl' => $propertyUrl,
                'companyName' => $companyName,
                'primaryColor' => $primaryColor,
                'emailHeaderColor' => $emailHeaderColor,
                'logoUrl' => $logoUrl,
                'websiteUrl' => $websiteUrl,
                'ownerEmail' => $client->owner_email,
            ]);

            Mail::html($emailHtml, function ($mail) use ($userEmail, $name, $subject, $companyName) {
                $mail->to($userEmail, $name)
                     ->subject($subject);
            });

            Log::info("Confirmation email sent for inquiry", [
                'to' => $userEmail,
                'property_ref' => $propertyRef,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send confirmation email", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Build HTML confirmation email for user.
     */
    protected function buildUserConfirmationEmailHtml(array $data): string
    {
        $name = e($data['name']);
        $propertyTitle = e($data['propertyTitle']);
        $propertyRef = e($data['propertyRef']);
        $propertyUrl = e($data['propertyUrl']);
        $companyName = e($data['companyName']);
        $primaryColor = $data['primaryColor'];
        $emailHeaderColor = $data['emailHeaderColor'];
        $logoUrl = $data['logoUrl'];
        $websiteUrl = $data['websiteUrl'];

        $logoHtml = $logoUrl
            ? "<img src=\"{$logoUrl}\" alt=\"{$companyName}\" style=\"max-width:180px;max-height:60px;margin-bottom:15px;display:block;margin-left:auto;margin-right:auto;\">"
            : ($companyName ? "<p style=\"margin:0 0 5px;font-size:14px;color:#ffffff;\">{$companyName}</p>" : '');

        $propertyRefHtml = $propertyRef ? "<p style=\"margin:0;font-size:14px;color:#666;\">Reference: {$propertyRef}</p>" : '';

        $propertyUrlButton = $propertyUrl ? "
            <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:20px auto;\">
                <tr>
                    <td bgcolor=\"{$primaryColor}\" style=\"background-color:{$primaryColor};border-radius:5px;\">
                        <a href=\"{$propertyUrl}\" style=\"display:inline-block;padding:12px 25px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;\">View Property</a>
                    </td>
                </tr>
            </table>" : '';

        $displayWebsiteUrl = $websiteUrl ? preg_replace('/^https?:\/\//', '', $websiteUrl) : '';
        $websiteLinkUrl = $websiteUrl ?: '#';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;line-height:1.6;color:#333333;background-color:#f4f4f4;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f4f4;">
        <tr>
            <td align="center" style="padding:20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;background-color:#ffffff;">
                    <!-- Header -->
                    <tr>
                        <td bgcolor="{$emailHeaderColor}" style="background-color:{$emailHeaderColor};padding:30px 20px;text-align:center;">
                            {$logoHtml}
                            <h2 style="margin:0;color:#ffffff;font-size:24px;font-family:Arial,sans-serif;">Thank You for Your Inquiry</h2>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:30px 20px;">
                            <p style="margin:0 0 20px;font-size:16px;color:#333;">Dear {$name},</p>

                            <p style="margin:0 0 20px;font-size:14px;color:#555;">
                                Thank you for your inquiry about <strong>{$propertyTitle}</strong>. We have received your message and will get back to you as soon as possible.
                            </p>

                            {$propertyRefHtml}
                            {$propertyUrlButton}

                            <p style="margin:20px 0;font-size:14px;color:#555;">
                                If you have any urgent questions, please don't hesitate to contact us directly.
                            </p>

                            <p style="margin:20px 0 0;font-size:14px;color:#333;">
                                Best regards,<br>
                                <strong>{$companyName}</strong>
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td bgcolor="#f0f0f0" style="background-color:#f0f0f0;padding:20px;text-align:center;font-size:12px;color:#777777;">
                            This is an automated confirmation email.<br>
                            <a href="{$websiteLinkUrl}" style="color:{$primaryColor};">{$displayWebsiteUrl}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Build HTML email for inquiry.
     */
    protected function buildInquiryEmailHtml(array $data): string
    {
        $name = e($data['name']);
        $email = e($data['email']);
        $phone = e($data['phone']);
        $message = nl2br(e($data['message']));
        $propertyRef = e($data['propertyRef']);
        $propertyTitle = e($data['propertyTitle']);
        $propertyUrl = e($data['propertyUrl']);
        $propertyPrice = e($data['propertyPrice']);
        $companyName = e($data['companyName']);
        $primaryColor = $data['primaryColor'];
        $emailHeaderColor = $data['emailHeaderColor'];
        $logoUrl = $data['logoUrl'];
        $websiteUrl = $data['websiteUrl'];

        $logoHtml = $logoUrl
            ? "<img src=\"{$logoUrl}\" alt=\"{$companyName}\" style=\"max-width:180px;max-height:60px;margin-bottom:15px;display:block;margin-left:auto;margin-right:auto;\">"
            : ($companyName ? "<p style=\"margin:0 0 5px;font-size:14px;color:#ffffff;\">{$companyName}</p>" : '');

        $propertyRefHtml = $propertyRef ? "<p style=\"margin:0 0 8px;font-size:14px;color:#333;\"><strong>Reference:</strong> {$propertyRef}</p>" : '';
        $propertyPriceHtml = $propertyPrice ? "<p style=\"margin:0 0 8px;font-size:14px;color:#333;\"><strong>Price:</strong> {$propertyPrice}</p>" : '';

        $propertyUrlButton = $propertyUrl ? "
            <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin-top:10px;\">
                <tr>
                    <td bgcolor=\"{$primaryColor}\" style=\"background-color:{$primaryColor};border-radius:5px;\">
                        <a href=\"{$propertyUrl}\" style=\"display:inline-block;padding:10px 20px;color:#ffffff;text-decoration:none;font-size:14px;\">View Property</a>
                    </td>
                </tr>
            </table>" : '';

        $phoneRowHtml = $phone ? "
            <tr>
                <td style=\"padding:10px 0;\">
                    <strong style=\"color:#555;font-size:13px;\">Phone</strong><br>
                    <a href=\"tel:{$phone}\" style=\"color:{$primaryColor};font-size:14px;\">{$phone}</a>
                </td>
            </tr>" : '';

        $messageHtml = $message ? "
            <h3 style=\"margin:0 0 15px;font-size:16px;color:#333;\">Message</h3>
            <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                <tr>
                    <td style=\"background-color:#fff9e6;padding:15px;border-left:4px solid #f39c12;font-size:14px;color:#555;\">
                        {$message}
                    </td>
                </tr>
            </table>" : '';

        $displayWebsiteUrl = $websiteUrl ? preg_replace('/^https?:\/\//', '', $websiteUrl) : 'smartpropertywidget.com';
        $websiteLinkUrl = $websiteUrl ?: 'https://smartpropertywidget.com';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;line-height:1.6;color:#333333;background-color:#f4f4f4;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f4f4;">
        <tr>
            <td align="center" style="padding:20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;background-color:#ffffff;">
                    <!-- Header -->
                    <tr>
                        <td bgcolor="{$emailHeaderColor}" style="background-color:{$emailHeaderColor};padding:30px 20px;text-align:center;">
                            {$logoHtml}
                            <h2 style="margin:0;color:#ffffff;font-size:24px;font-family:Arial,sans-serif;">New Property Inquiry</h2>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td bgcolor="#f9f9f9" style="background-color:#f9f9f9;padding:20px;">
                            <!-- Property Info -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:20px;border:1px solid #e0e0e0;">
                                <tr>
                                    <td bgcolor="#ffffff" style="background-color:#ffffff;padding:15px;border-left:4px solid {$primaryColor};">
                                        <h3 style="margin:0 0 15px;font-size:16px;color:#333;">Property Details</h3>
                                        <p style="margin:0 0 8px;font-size:14px;color:#333;"><strong>Title:</strong> {$propertyTitle}</p>
                                        {$propertyRefHtml}
                                        {$propertyPriceHtml}
                                        {$propertyUrlButton}
                                    </td>
                                </tr>
                            </table>

                            <!-- Contact Information -->
                            <h3 style="margin:0 0 15px;font-size:16px;color:#333;">Contact Information</h3>
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:15px;">
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #eee;">
                                        <strong style="color:#555;font-size:13px;">Name</strong><br>
                                        <span style="color:#333;font-size:14px;">{$name}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #eee;">
                                        <strong style="color:#555;font-size:13px;">Email</strong><br>
                                        <a href="mailto:{$email}" style="color:{$primaryColor};font-size:14px;">{$email}</a>
                                    </td>
                                </tr>
                                {$phoneRowHtml}
                            </table>

                            {$messageHtml}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td bgcolor="#f0f0f0" style="background-color:#f0f0f0;padding:20px;text-align:center;font-size:12px;color:#777777;">
                            This inquiry was sent via {$companyName}<br>
                            <a href="{$websiteLinkUrl}" style="color:{$primaryColor};">{$displayWebsiteUrl}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
