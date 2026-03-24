<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\WidgetAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected WidgetAnalyticsService $analyticsService,
    ) {}

    /**
     * Main dashboard - shows widget analytics.
     */
    public function index(Request $request)
    {
        $client = Auth::user()->client;
        $period = $request->input('period', '30');

        if (!$client->domain) {
            return view('client.widget.analytics', [
                'stats'            => [],
                'chartData'        => ['labels' => [], 'searches' => [], 'views' => [], 'inquiries' => []],
                'topLocations'     => [],
                'topPropertyTypes' => [],
                'topProperties'    => [],
                'period'           => $period,
                'apiDown'          => false,
            ]);
        }

        $data = $this->analyticsService->getAllAnalytics($client->domain, $period);
        $apiDown = $data['error'] ?? false;

        // Map summary response to stat card values
        $summary = $data['summary']['stats'] ?? [];
        $totalViews = $summary['property_views'] ?? 0;
        $totalInquiries = $summary['inquiries'] ?? 0;
        $conversionRate = $totalViews > 0 ? round(($totalInquiries / $totalViews) * 100, 1) : 0;

        $stats = [
            'searches'       => $summary['searches'] ?? 0,
            'property_views' => $summary['property_views'] ?? 0,
            'card_clicks'    => $summary['card_clicks'] ?? 0,
            'wishlist_adds'  => $summary['wishlist_adds'] ?? 0,
            'inquiries'      => $totalInquiries,
            'pdf_downloads'  => $summary['pdf_downloads'] ?? 0,
            'video_views'    => $summary['video_views'] ?? 0,
            'tour_views'     => $summary['tour_views'] ?? 0,
            'conversion_rate' => $conversionRate,
            'unique_sessions' => $data['summary']['unique_sessions'] ?? 0,
            'total_events'    => $data['summary']['total_events'] ?? 0,
        ];

        // Map trends response for Chart.js
        $trends = $data['trends'] ?? [];
        $chartData = [
            'labels'    => $trends['labels'] ?? [],
            'searches'  => $trends['datasets']['searches'] ?? [],
            'views'     => $trends['datasets']['property_views'] ?? [],
            'inquiries' => $trends['datasets']['inquiries'] ?? [],
        ];

        // Map top properties (limit to 10)
        $topProperties = array_slice($data['properties']['properties'] ?? [], 0, 10);

        // Map search insights
        $searchData = $data['searches'] ?? [];
        $totalSearchCount = $searchData['total_searches'] ?? 1;

        $topLocations = collect($searchData['top_locations'] ?? [])
            ->map(fn ($count, $name) => [
                'name'       => $name,
                'count'      => $count,
                'percentage' => $totalSearchCount > 0 ? round(($count / $totalSearchCount) * 100, 1) : 0,
            ])
            ->values()
            ->take(10)
            ->all();

        $topPropertyTypes = collect($searchData['top_property_types'] ?? [])
            ->map(fn ($count, $name) => [
                'name'       => $name,
                'count'      => $count,
                'percentage' => $totalSearchCount > 0 ? round(($count / $totalSearchCount) * 100, 1) : 0,
            ])
            ->values()
            ->take(10)
            ->all();

        return view('client.widget.analytics', compact(
            'stats', 'chartData', 'topLocations', 'topPropertyTypes', 'topProperties', 'period', 'apiDown'
        ));
    }
}
