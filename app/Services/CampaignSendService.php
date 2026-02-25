<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\GlobalSuppression;
use App\Models\SuppressionList;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CampaignSendService
{
    public function __construct(private EmailContentService $content) {}

    /**
     * Orchestrate a full campaign dispatch via n8n.
     *
     * @throws \RuntimeException if campaign is missing required data
     */
    public function dispatch(Campaign $campaign): void
    {
        // 1. Validate campaign has everything needed
        if (!$campaign->list_id) {
            throw new \RuntimeException('Campaign has no contact list assigned.');
        }
        if (!$campaign->smtp_account_id) {
            throw new \RuntimeException('Campaign has no SMTP account assigned.');
        }
        if (!$campaign->html_content) {
            throw new \RuntimeException('Campaign has no HTML content.');
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
     * Send a single test email (no tracking, no unsubscribe footer) via n8n.
     */
    public function sendTest(Campaign $campaign, string $testEmail): void
    {
        if (!$campaign->smtp_account_id) {
            throw new \RuntimeException('Campaign has no SMTP account assigned.');
        }

        // BUG FIX: guard against deleted SMTP account
        $smtp = $campaign->smtpAccount;
        if (!$smtp) {
            throw new \RuntimeException('SMTP account not found.');
        }

        $password = Crypt::decryptString($smtp->password_encrypted);
        $subject  = '[TEST] ' . $campaign->subject;
        $html     = $campaign->html_content ?? '<p>No content</p>';

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
