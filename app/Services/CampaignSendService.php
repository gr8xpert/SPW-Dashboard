<?php

namespace App\Services;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\GlobalSuppression;
use App\Models\SuppressionList;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CampaignSendService
{
    public function __construct(private EmailContentService $content) {}

    /**
     * Orchestrate a full campaign dispatch.
     * Uses native Laravel Mail (sendmail) if no SMTP account is configured,
     * otherwise sends via n8n for external SMTP accounts.
     *
     * @throws \RuntimeException if campaign is missing required data
     */
    public function dispatch(Campaign $campaign): void
    {
        // 1. Validate campaign has everything needed
        if (!$campaign->list_id) {
            throw new \RuntimeException('Campaign has no contact list assigned.');
        }
        if (!$campaign->html_content) {
            throw new \RuntimeException('Campaign has no HTML content.');
        }

        // 2. Route to appropriate sender
        if (!$campaign->smtp_account_id) {
            // Use native Laravel Mail (sendmail)
            $this->dispatchViaNativeMail($campaign);
            return;
        }

        // 2. Resolve and decrypt SMTP credentials
        $smtp = $campaign->smtpAccount;
        if (!$smtp) {
            throw new \RuntimeException('SMTP account not found.');
        }
        $password = Crypt::decryptString($smtp->password_encrypted);

        // 3. Guard: contact list may have been deleted since campaign was created
        $list = $campaign->list;
        if (!$list) {
            throw new \RuntimeException('Contact list has been deleted.');
        }

        // 4. Build suppression exclusion list
        $suppressedGlobal = GlobalSuppression::pluck('email')->toArray();
        $suppressedClient = SuppressionList::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('client_id', $campaign->client_id)
            ->pluck('email')
            ->toArray();
        $suppressed = array_unique(array_merge($suppressedGlobal, $suppressedClient));

        // 5. Get eligible subscribed contacts (TenantScope auto-disabled in console context)
        $contacts = $list->contacts()
            ->where('status', 'subscribed')
            ->whereNotIn('email', $suppressed)
            ->get();

        if ($contacts->isEmpty()) {
            $campaign->update(['status' => 'sent', 'completed_at' => now()]);
            return;
        }

        // 6. Create CampaignSend records and personalize content
        $sends = [];
        foreach ($contacts as $contact) {
            $send = CampaignSend::create([
                'client_id'   => $campaign->client_id,
                'campaign_id' => $campaign->id,
                'contact_id'  => $contact->id,
                'status'      => 'pending',
            ]);

            $built = $this->content->build($campaign, $contact, $send->id);
            $send->update(['personalized_html' => $built['html']]);

            $sends[] = [
                'send_id'    => $send->id,
                'to_email'   => $contact->email,
                'to_name'    => $contact->full_name,
                'subject'    => $built['subject'],
                'html'       => $built['html'],
                'plain_text' => $built['plainText'],
            ];
        }

        // 7. Mark campaign as sending
        $campaign->update(['status' => 'sending', 'started_at' => now()]);

        // 8. POST batch payload to n8n
        $payload = [
            'campaign_id' => $campaign->id,
            'smtp' => [
                'host'       => $smtp->host,
                'port'       => $smtp->port,
                'username'   => $smtp->username,
                'password'   => $password,
                'encryption' => $smtp->encryption,
                'from_name'  => $smtp->from_name  ?: $campaign->from_name,
                'from_email' => $smtp->from_email ?: $campaign->from_email,
            ],
            'sends'           => $sends,
            'callback_url'    => url('/api/n8n/callback'),
            'callback_secret' => config('smartmailer.n8n_api_key'),
        ];

        $webhookUrl = rtrim(config('smartmailer.n8n_webhook_base'), '/') . '/webhook/campaign-send';

        try {
            Http::withHeaders(['X-N8N-Key' => config('smartmailer.n8n_api_key')])
                ->timeout(30)
                ->post($webhookUrl, $payload);
        } catch (\Throwable $e) {
            Log::error('CampaignSendService: n8n webhook failed', [
                'campaign_id' => $campaign->id,
                'error'       => $e->getMessage(),
            ]);

            CampaignSend::where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed', 'bounce_reason' => 'n8n webhook unreachable']);
            $campaign->update(['status' => 'failed']);

            throw $e;
        }
    }

    /**
     * Dispatch campaign using native Laravel Mail (sendmail).
     * This is used when no SMTP account is configured.
     */
    protected function dispatchViaNativeMail(Campaign $campaign): void
    {
        // 1. Guard: contact list may have been deleted
        $list = $campaign->list;
        if (!$list) {
            throw new \RuntimeException('Contact list has been deleted.');
        }

        // 2. Build suppression exclusion list
        $suppressedGlobal = GlobalSuppression::pluck('email')->toArray();
        $suppressedClient = SuppressionList::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('client_id', $campaign->client_id)
            ->pluck('email')
            ->toArray();
        $suppressed = array_unique(array_merge($suppressedGlobal, $suppressedClient));

        // 3. Get eligible subscribed contacts
        $contacts = $list->contacts()
            ->where('status', 'subscribed')
            ->whereNotIn('email', $suppressed)
            ->get();

        if ($contacts->isEmpty()) {
            $campaign->update(['status' => 'sent', 'completed_at' => now()]);
            return;
        }

        // 4. Mark campaign as sending
        $campaign->update(['status' => 'sending', 'started_at' => now()]);

        $sentCount = 0;
        $failedCount = 0;

        // 5. Send emails via Laravel Mail
        foreach ($contacts as $contact) {
            $send = CampaignSend::create([
                'client_id'   => $campaign->client_id,
                'campaign_id' => $campaign->id,
                'contact_id'  => $contact->id,
                'status'      => 'pending',
            ]);

            try {
                $built = $this->content->build($campaign, $contact, $send->id);
                $send->update(['personalized_html' => $built['html']]);

                $mail = new CampaignMail(
                    campaignSubject: $built['subject'],
                    htmlContent: $built['html'],
                    plainText: $built['plainText'] ?: null,
                    fromName: $campaign->from_name,
                    fromEmail: $campaign->from_email,
                    campaignReplyTo: $campaign->reply_to,
                );

                Mail::to($contact->email, $contact->full_name)->send($mail);

                $send->update([
                    'status'  => 'sent',
                    'sent_at' => now(),
                ]);
                $sentCount++;

                Log::info('Campaign email sent via native mail', [
                    'campaign_id' => $campaign->id,
                    'send_id'     => $send->id,
                    'to'          => $contact->email,
                ]);
            } catch (\Throwable $e) {
                $send->update([
                    'status'        => 'failed',
                    'bounce_reason' => substr($e->getMessage(), 0, 500),
                ]);
                $failedCount++;

                Log::error('Campaign email failed via native mail', [
                    'campaign_id' => $campaign->id,
                    'send_id'     => $send->id,
                    'to'          => $contact->email,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        // 6. Update campaign status
        $campaign->update([
            'status'       => $failedCount === count($contacts) ? 'failed' : 'sent',
            'completed_at' => now(),
        ]);

        Log::info('Campaign dispatch completed via native mail', [
            'campaign_id' => $campaign->id,
            'sent'        => $sentCount,
            'failed'      => $failedCount,
        ]);
    }

    /**
     * Send a single test email (no tracking, no unsubscribe footer).
     * Uses native Laravel Mail if no SMTP account configured, otherwise via n8n.
     */
    public function sendTest(Campaign $campaign, string $testEmail): void
    {
        $subject = '[TEST] ' . $campaign->subject;
        $html    = $campaign->html_content ?? '<p>No content</p>';

        // Use native mail if no SMTP account configured
        if (!$campaign->smtp_account_id) {
            $mail = new CampaignMail(
                campaignSubject: $subject,
                htmlContent: $html,
                plainText: $campaign->plain_text_content ?: null,
                fromName: $campaign->from_name,
                fromEmail: $campaign->from_email,
                campaignReplyTo: $campaign->reply_to,
            );

            Mail::to($testEmail)->send($mail);

            Log::info('Campaign test email sent via native mail', [
                'campaign_id' => $campaign->id,
                'to'          => $testEmail,
            ]);
            return;
        }

        // Otherwise use n8n with external SMTP
        $smtp = $campaign->smtpAccount;
        if (!$smtp) {
            throw new \RuntimeException('SMTP account not found.');
        }

        $password = Crypt::decryptString($smtp->password_encrypted);

        $payload = [
            'campaign_id' => $campaign->id,
            'smtp' => [
                'host'       => $smtp->host,
                'port'       => $smtp->port,
                'username'   => $smtp->username,
                'password'   => $password,
                'encryption' => $smtp->encryption,
                'from_name'  => $smtp->from_name  ?: $campaign->from_name,
                'from_email' => $smtp->from_email ?: $campaign->from_email,
            ],
            'sends' => [[
                'send_id'    => 0,
                'to_email'   => $testEmail,
                'to_name'    => 'Test Recipient',
                'subject'    => $subject,
                'html'       => $html,
                'plain_text' => $campaign->plain_text_content ?? '',
            ]],
            'callback_url'    => null,
            'callback_secret' => null,
        ];

        $webhookUrl = rtrim(config('smartmailer.n8n_webhook_base'), '/') . '/webhook/campaign-send';

        Http::withHeaders(['X-N8N-Key' => config('smartmailer.n8n_api_key')])
            ->timeout(30)
            ->post($webhookUrl, $payload);
    }
}
