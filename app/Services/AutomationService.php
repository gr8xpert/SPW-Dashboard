<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\Contact;
use App\Models\SmtpAccount;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    public function checkAndFire(string $triggerType, Contact $contact, array $context = []): void
    {
        $automations = Automation::where('trigger_type', $triggerType)
            ->where('status', 'active')
            ->where('client_id', $contact->client_id)
            ->with('steps')
            ->get();

        foreach ($automations as $automation) {
            $this->fireAutomation($automation, $contact, $context);
        }
    }

    private function fireAutomation(Automation $automation, Contact $contact, array $context): void
    {
        $smtp = SmtpAccount::where('client_id', $contact->client_id)
            ->where('is_default', true)
            ->first()
            ?? SmtpAccount::where('client_id', $contact->client_id)->first();

        if (!$smtp) {
            Log::warning('AutomationService: no SMTP account for client', ['client_id' => $contact->client_id]);
            return;
        }

        try {
            $password = Crypt::decryptString($smtp->password_encrypted);
        } catch (\Throwable $e) {
            Log::error('AutomationService: SMTP password decrypt failed', ['smtp_id' => $smtp->id]);
            return;
        }

        $webhookUrl = rtrim(config('smartmailer.n8n_webhook_base'), '/') . '/webhook/automation';

        foreach ($automation->steps as $step) {
            if ($step->step_type !== 'send_email') {
                continue;
            }

            $cfg     = $step->config ?? [];
            $subject = $this->personalize($cfg['subject'] ?? 'Hello, {{first_name}}!', $contact);
            $html    = $this->personalize($cfg['html'] ?? $cfg['template'] ?? '<p>Hi {{first_name}},</p>', $contact);

            $payload = [
                'automation_id' => $automation->id,
                'delay_minutes' => (int) ($cfg['delay_minutes'] ?? 0),
                'smtp' => [
                    'host'       => $smtp->host,
                    'port'       => $smtp->port,
                    'username'   => $smtp->username,
                    'password'   => $password,
                    'encryption' => $smtp->encryption,
                    'from_name'  => $smtp->from_name ?? config('app.name'),
                    'from_email' => $smtp->from_email,
                ],
                'email' => [
                    'to_email' => $contact->email,
                    'to_name'  => $contact->full_name,
                    'subject'  => $subject,
                    'html'     => $html,
                ],
                'callback_url'    => url('/api/n8n/callback'),
                'callback_secret' => config('smartmailer.n8n_api_key'),
            ];

            try {
                Http::withHeaders(['X-N8N-Key' => config('smartmailer.n8n_api_key')])
                    ->timeout(15)
                    ->post($webhookUrl, $payload);
            } catch (\Throwable $e) {
                Log::error('AutomationService: n8n webhook failed', [
                    'automation_id' => $automation->id,
                    'contact_id'    => $contact->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }
    }

    private function personalize(string $text, Contact $contact): string
    {
        return str_replace(
            ['{{first_name}}', '{{last_name}}', '{{email}}', '{{company}}', '{{full_name}}'],
            [
                $contact->first_name ?? '',
                $contact->last_name  ?? '',
                $contact->email,
                $contact->company    ?? '',
                $contact->full_name,
            ],
            $text
        );
    }
}
