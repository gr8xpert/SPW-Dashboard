<?php

namespace App\Services;

use App\Http\Controllers\Api\TrackingController;
use App\Models\Campaign;
use App\Models\Contact;

class EmailContentService
{
    public function __construct(
        protected PersonalizationService $personalization
    ) {}

    /**
     * Build personalized, tracked HTML email for a single contact.
     *
     * @return array{subject: string, html: string, plainText: string}
     */
    public function build(Campaign $campaign, Contact $contact, int $sendId): array
    {
        $appUrl = rtrim(config('smartmailer.tracking_domain') ?? config('app.url'), '/');

        // 1. Personalize subject and body using shared service
        $subject   = $this->personalization->personalize($campaign->subject, $contact);
        $html      = $this->personalization->personalize($campaign->html_content ?? '', $contact);
        $plainText = $this->personalization->personalize($campaign->plain_text_content ?? '', $contact);

        // 2. Brand kit wrapper (if client has one)
        //    Extract inner content first to prevent double <body> nesting
        $brandKit = $campaign->client?->brandKit;
        if ($brandKit) {
            $html = $this->wrapWithBrandKit($html, $brandKit);
        }

        // 3. Click-tracking: wrap all external hrefs
        $hash = TrackingController::encodeHash($sendId, $contact->id, $campaign->id);
        $html = $this->wrapClickLinks($html, $hash, $appUrl);

        // 4. Unsubscribe footer
        $unsubToken = $contact->double_opt_in_token ?? '';
        $footer     = $this->buildUnsubscribeFooter($appUrl . '/unsubscribe/' . $unsubToken);

        // 5. Open-tracking pixel
        $pixel = '<img src="' . $appUrl . '/t/o/' . $hash . '" width="1" height="1" style="display:none" alt="">';

        // Insert footer + pixel before the LAST </body>, or append if none
        if (stripos($html, '</body>') !== false) {
            // Use preg_replace with limit=1 to replace only the LAST </body>
            $html = preg_replace('/<\/body>/i', $footer . $pixel . '</body>', $html, 1);
        } else {
            $html .= $footer . $pixel;
        }

        return compact('subject', 'html', 'plainText');
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    private function wrapWithBrandKit(string $html, $brandKit): string
    {
        // BUG FIX: If the content is already a full HTML document, extract only
        // the inner body content to prevent double <body>/<html> nesting,
        // which would cause the tracking pixel and footer to be injected twice.
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $html = trim($matches[1]);
        } elseif (stripos($html, '<html') !== false) {
            $html = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $html);
            $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
            $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
            $html = trim($html);
        }

        $primaryColor = htmlspecialchars($brandKit->primary_color ?? '#4a90d9');
        $fontBody     = htmlspecialchars($brandKit->font_body     ?? 'Arial, sans-serif');

        $logoHtml = $brandKit->logo_url
            ? '<img src="' . htmlspecialchars($brandKit->logo_url) . '" alt="Logo" style="max-height:60px;max-width:200px;">'
            : '';

        $kitFooter = $brandKit->footer_html
            ? '<tr><td style="padding:20px;background:#f8f8f8;">' . $brandKit->footer_html . '</td></tr>'
            : '';

        return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:' . $fontBody . ';">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;">
    <tr><td align="center" style="padding:20px 0;">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:8px;overflow:hidden;">
        <tr><td style="background:' . $primaryColor . ';padding:20px;text-align:center;">' . $logoHtml . '</td></tr>
        <tr><td style="padding:30px;">' . $html . '</td></tr>
        ' . $kitFooter . '
      </table>
    </td></tr>
  </table>
</body>
</html>';
    }

    private function wrapClickLinks(string $html, string $hash, string $appUrl): string
    {
        return preg_replace_callback(
            '/href=["\']([^"\']+)["\']/i',
            function (array $m) use ($hash, $appUrl): string {
                $url = $m[1];
                // Skip non-http, already-tracking, and unsubscribe links
                if (
                    (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://'))
                    || str_contains($url, '/unsubscribe/')
                    || str_contains($url, '/t/c/')
                    || str_contains($url, '/t/o/')
                ) {
                    return $m[0];
                }
                return 'href="' . $appUrl . '/t/c/' . $hash . '?url=' . urlencode($url) . '"';
            },
            $html
        ) ?? $html;
    }

    private function buildUnsubscribeFooter(string $unsubscribeUrl): string
    {
        return '
<div style="text-align:center;padding:20px;font-size:12px;color:#999;font-family:Arial,sans-serif;">
  You are receiving this email because you subscribed to our list.<br>
  <a href="' . htmlspecialchars($unsubscribeUrl) . '" style="color:#999;">Unsubscribe</a>
</div>';
    }
}
