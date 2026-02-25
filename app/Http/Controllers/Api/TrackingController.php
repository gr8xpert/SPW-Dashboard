<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampaignSend;
use App\Models\EmailEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    // 1x1 transparent GIF pixel
    private const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function open(string $hash)
    {
        try {
            [$sendId, $contactId, $campaignId] = $this->decodeHash($hash);

            $send = CampaignSend::find($sendId);

            if ($send && !$send->opened_at) {
                $send->update(['opened_at' => now(), 'status' => 'opened']);

                EmailEvent::create([
                    'client_id'        => $send->client_id,
                    'campaign_send_id' => $sendId,
                    'campaign_id'      => $campaignId,
                    'contact_id'       => $contactId,
                    'event_type'       => 'open',
                    'user_agent'       => request()->userAgent(),
                    'ip_address'       => request()->ip(),
                    'device_type'      => $this->detectDevice(request()->userAgent()),
                ]);

                // Update contact stats
                $send->contact?->increment('total_opens');
                $send->contact?->update(['last_opened_at' => now()]);

                // Update campaign counter
                $send->campaign?->increment('total_opened');
            }
        } catch (\Throwable $e) {
            Log::debug('Tracking open error: ' . $e->getMessage());
        }

        return response(base64_decode(self::PIXEL), 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }

    public function click(string $hash, Request $request)
    {
        $redirectUrl = '/';

        try {
            [$sendId, $contactId, $campaignId] = $this->decodeHash($hash);
            $url = $request->query('url');

            if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                $redirectUrl = $url;
            }

            $send = CampaignSend::find($sendId);

            if ($send) {
                if (!$send->clicked_at) {
                    $send->update(['clicked_at' => now(), 'status' => 'clicked']);
                    $send->campaign?->increment('total_clicked');
                    $send->contact?->increment('total_clicks');
                    $send->contact?->update(['last_clicked_at' => now()]);
                }

                EmailEvent::create([
                    'client_id'        => $send->client_id,
                    'campaign_send_id' => $sendId,
                    'campaign_id'      => $campaignId,
                    'contact_id'       => $contactId,
                    'event_type'       => 'click',
                    'link_url'         => $url,
                    'user_agent'       => request()->userAgent(),
                    'ip_address'       => request()->ip(),
                    'device_type'      => $this->detectDevice(request()->userAgent()),
                ]);
            }
        } catch (\Throwable $e) {
            Log::debug('Tracking click error: ' . $e->getMessage());
        }

        return redirect($redirectUrl);
    }

    protected function decodeHash(string $hash): array
    {
        $decoded = base64_decode(strtr($hash, '-_', '+/'));
        return explode(':', $decoded);
    }

    public static function encodeHash(int $sendId, int $contactId, int $campaignId): string
    {
        return strtr(base64_encode("{$sendId}:{$contactId}:{$campaignId}"), '+/', '-_');
    }

    protected function detectDevice(?string $userAgent): string
    {
        if (!$userAgent) return 'unknown';
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android')) return 'mobile';
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) return 'tablet';
        return 'desktop';
    }
}
