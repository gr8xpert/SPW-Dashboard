<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\WidgetAnalytic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetAnalyticsController extends Controller
{
    /**
     * POST /api/v1/widget/analytics
     *
     * Batch ingest widget analytics events.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => 'required|string',
            'events' => 'required|array|min:1|max:100',
            'events.*.type'       => 'required|string|in:search,property_view,card_click,wishlist_add,inquiry,share,pdf_download',
            'events.*.data'       => 'nullable|array',
            'events.*.session_id' => 'nullable|string|max:64',
            'events.*.url'        => 'nullable|string|max:500',
        ]);

        $client = Client::where('domain', $request->domain)->first();
        if (!$client) {
            return response()->json(['error' => 'Unknown domain'], 404);
        }

        $rows = [];
        $now = now();
        foreach ($request->events as $event) {
            $rows[] = [
                'client_id'  => $client->id,
                'event_type' => $event['type'],
                'event_data' => isset($event['data']) ? json_encode($event['data']) : null,
                'session_id' => $event['session_id'] ?? null,
                'url'        => $event['url'] ?? null,
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'created_at' => $now,
            ];
        }

        WidgetAnalytic::insert($rows);

        return response()->json(['success' => true, 'count' => count($rows)]);
    }
}
