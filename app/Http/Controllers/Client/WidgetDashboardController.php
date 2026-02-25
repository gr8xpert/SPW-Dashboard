<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\WidgetAnalytic;
use App\Services\WidgetSubscriptionService;
use Illuminate\Http\Request;

class WidgetDashboardController extends Controller
{
    public function __construct(
        protected WidgetSubscriptionService $subscriptionService
    ) {}

    /**
     * Widget status overview.
     */
    public function index()
    {
        $client = auth()->user()->client;
        $client->load('plan');

        $subscriptionInfo = null;
        if ($client->domain) {
            $subscriptionInfo = $this->subscriptionService->checkSubscription($client->domain);
        }

        return view('client.widget.index', compact('client', 'subscriptionInfo'));
    }

    /**
     * Widget analytics: property views, searches, inquiry conversions.
     */
    public function analytics(Request $request)
    {
        $client = auth()->user()->client;
        $days = $request->input('days', 30);
        $from = now()->subDays($days)->startOfDay();

        $events = WidgetAnalytic::forClient($client->id)
            ->where('created_at', '>=', $from)
            ->selectRaw('event_type, DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('event_type', 'date')
            ->orderBy('date')
            ->get();

        $totalSearches = $events->where('event_type', 'search')->sum('count');
        $totalViews = $events->where('event_type', 'property_view')->sum('count');
        $totalInquiries = $events->where('event_type', 'inquiry')->sum('count');
        $inquiryRate = $totalViews > 0 ? round(($totalInquiries / $totalViews) * 100, 1) : 0;

        return view('client.widget.analytics', compact(
            'events', 'totalSearches', 'totalViews', 'totalInquiries', 'inquiryRate', 'days'
        ));
    }

    /**
     * Widget setup: license key, domain, WordPress instructions.
     */
    public function setup()
    {
        $client = auth()->user()->client;
        $client->load('licenseKeys');

        $embedCode = $this->generateEmbedCode($client);

        return view('client.widget.setup', compact('client', 'embedCode'));
    }

    /**
     * Contacts captured from widget inquiries.
     */
    public function inquiryContacts()
    {
        $client = auth()->user()->client;

        $contacts = $client->contacts ?? collect();

        // If Contact model has source field, filter by widget_inquiry
        // For now, we'll rely on the "Widget Inquiries" list
        $list = $client->contactLists ?? collect();

        return view('client.widget.inquiry-contacts', compact('client'));
    }

    protected function generateEmbedCode($client): string
    {
        $proxyUrl = config('smartmailer.widget_proxy_url', 'https://smartpropertywidget.com/spw/dist');

        return <<<HTML
<!-- Smart Property Widget -->
<div id="realtysoft-widget"></div>
<link rel="stylesheet" href="{$proxyUrl}/style.css">
<script src="{$proxyUrl}/realtysoft.js"></script>
<script>
  RealtySoft.init({
    container: '#realtysoft-widget',
    domain: '{$client->domain}',
    language: '{$client->default_language}'
  });
</script>
HTML;
    }
}
