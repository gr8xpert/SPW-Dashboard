<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignQueue;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function campaignStatus(Request $request)
    {
        $data = $request->validate([
            'campaign_id' => 'required|integer',
            'action'      => 'required|string',
            'total'       => 'sometimes|integer',
            'progress'    => 'sometimes|integer',
        ]);

        $campaign = Campaign::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->find($data['campaign_id']);

        if (!$campaign) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }

        switch ($data['action']) {
            case 'start':
                $campaign->update(['status' => 'sending', 'started_at' => now()]);
                break;
            case 'complete':
                $campaign->update(['status' => 'sent', 'completed_at' => now()]);
                CampaignQueue::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('campaign_id', $campaign->id)
                    ->update(['status' => 'completed', 'completed_at' => now()]);
                break;
            case 'fail':
                $campaign->update(['status' => 'failed']);
                break;
        }

        return response()->json(['success' => true]);
    }
}
