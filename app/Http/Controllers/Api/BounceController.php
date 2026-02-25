<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampaignSend;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Models\GlobalSuppression;
use App\Models\SuppressionList;
use Illuminate\Http\Request;

class BounceController extends Controller
{
    public function handle(Request $request)
    {
        $email       = $request->input('email');
        $bounceType  = $request->input('type', 'hard');
        $reason      = $request->input('reason', 'Bounce');
        $messageId   = $request->input('smtp_message_id');

        $send = CampaignSend::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->when($messageId, fn($q) => $q->where('smtp_message_id', $messageId))
            ->latest()
            ->first();

        if ($send) {
            $send->update([
                'status'       => 'bounced',
                'bounce_type'  => $bounceType,
                'bounce_reason'=> $reason,
                'bounced_at'   => now(),
            ]);

            $send->campaign?->increment('total_bounced');

            EmailEvent::create([
                'client_id'        => $send->client_id,
                'campaign_send_id' => $send->id,
                'campaign_id'      => $send->campaign_id,
                'contact_id'       => $send->contact_id,
                'event_type'       => 'bounce',
            ]);

            if ($bounceType === 'hard') {
                // Mark contact as bounced
                Contact::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('id', $send->contact_id)
                    ->update(['status' => 'bounced']);

                // Add to client suppression
                SuppressionList::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->updateOrCreate(
                        ['client_id' => $send->client_id, 'email' => $email],
                        ['reason' => 'hard_bounce']
                    );

                // Add to global suppression
                GlobalSuppression::updateOrCreate(
                    ['email' => strtolower($email)],
                    ['reason' => 'hard_bounce', 'source_client_id' => $send->client_id]
                );
            }
        }

        return response()->json(['success' => true]);
    }

    public function complaint(Request $request)
    {
        $email = strtolower($request->input('email'));

        GlobalSuppression::updateOrCreate(
            ['email' => $email],
            ['reason' => 'complaint']
        );

        Contact::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('email', $email)
            ->update(['status' => 'complained']);

        return response()->json(['success' => true]);
    }
}
