<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampaignSend;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class N8nCallbackController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // SECURITY FIX: empty() catches null, '', 0, false —
        // prevents auth bypass when N8N_API_KEY is not set in .env
        $apiKey = config('smartmailer.n8n_api_key');
        if (empty($apiKey) || $request->header('X-Callback-Secret') !== $apiKey) {
            Log::warning('N8nCallback: unauthorized request', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $sendId    = $request->input('send_id');
        $status    = $request->input('status'); // 'sent' | 'failed'
        $error     = $request->input('error');
        $smtpMsgId = $request->input('smtp_message_id');

        // BUG FIX: cast to int — send_id of 0 means automation email (no
        // CampaignSend record). Acknowledge silently instead of returning 422.
        $sendId = (int) $sendId;
        if ($sendId <= 0) {
            return response()->json(['ok' => true]);
        }

        $send = CampaignSend::find($sendId);
        if (!$send) {
            return response()->json(['error' => 'CampaignSend not found'], 404);
        }

        if ($status === 'sent') {
            $send->update([
                'status'          => 'sent',
                'sent_at'         => now(),
                'smtp_message_id' => $smtpMsgId,
            ]);
            $send->campaign?->increment('total_sent');
        } else {
            $send->update([
                'status'        => 'failed',
                'bounce_type'   => 'hard',
                'bounce_reason' => $error ?? 'Delivery failed',
            ]);
            $send->campaign?->increment('total_bounced');
        }

        // Mark campaign as sent when no more pending sends remain
        $campaign = $send->campaign;
        if ($campaign && $campaign->status === 'sending') {
            $pendingCount = CampaignSend::where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->count();

            if ($pendingCount === 0) {
                $campaign->update([
                    'status'       => 'sent',
                    'completed_at' => now(),
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }
}
