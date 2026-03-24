<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Inquiry;
use App\Services\WidgetAnalyticsService;
use App\Services\WidgetSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WidgetDashboardController extends Controller
{
    public function __construct(
        protected WidgetSubscriptionService $subscriptionService,
        protected WidgetAnalyticsService $analyticsService,
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

        // Build plan features list
        $features = [];
        if ($client->plan) {
            $planFeatures = $client->plan->features ?? [];

            // Standard widget features
            $featureDefinitions = [
                'widget_included' => 'Property Search Widget',
                'ai_search_enabled' => 'AI-Powered Search',
                'multi_language' => 'Multi-Language Support',
                'custom_branding' => 'Custom Branding',
                'analytics' => 'Widget Analytics',
                'inquiry_forms' => 'Inquiry Forms',
                'wishlist' => 'Wishlist Feature',
                'currency_converter' => 'Currency Converter',
                'map_view' => 'Map View',
                'pdf_downloads' => 'PDF Downloads',
            ];

            foreach ($featureDefinitions as $key => $name) {
                // Check plan-level booleans first, then features array
                $enabled = false;
                if ($key === 'widget_included') {
                    $enabled = $client->plan->widget_included ?? false;
                } elseif ($key === 'ai_search_enabled') {
                    $enabled = $client->plan->ai_search_enabled ?? false;
                } else {
                    $enabled = $planFeatures[$key] ?? false;
                }

                $features[] = ['name' => $name, 'enabled' => $enabled];
            }
        }

        // Fetch quick stats from analytics (last 30 days)
        $stats = [
            'searches' => 0,
            'views' => 0,
            'inquiries' => 0,
            'ai_searches' => 0,
        ];

        if ($client->domain) {
            $analyticsData = $this->analyticsService->getAllAnalytics($client->domain, '30');
            if (empty($analyticsData['error'])) {
                $summary = $analyticsData['summary']['stats'] ?? [];
                $stats = [
                    'searches' => $summary['searches'] ?? 0,
                    'views' => $summary['property_views'] ?? 0,
                    'inquiries' => $summary['inquiries'] ?? 0,
                    'ai_searches' => $summary['ai_searches'] ?? 0,
                ];
            }
        }

        return view('client.widget.index', compact('client', 'subscriptionInfo', 'features', 'stats'));
    }

    /**
     * Widget analytics: property views, searches, inquiry conversions.
     * Fetches real data from the RealtysoftV3 PHP analytics API.
     */
    public function analytics(Request $request)
    {
        $client = auth()->user()->client;
        $period = $request->input('period', '30');

        if (!$client->domain) {
            return view('client.widget.analytics', [
                'stats'            => [],
                'chartData'        => ['labels' => [], 'searches' => [], 'views' => [], 'inquiries' => []],
                'topLocations'     => [],
                'topPropertyTypes' => [],
                'topProperties'    => [],
                'allProperties'    => [],
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

        // Get all properties and top 10
        $allProperties = $data['properties']['properties'] ?? [];
        $topProperties = array_slice($allProperties, 0, 10);

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

        // Debug: ensure allProperties is set
        $debugInfo = [
            'allPropertiesCount' => count($allProperties),
            'topPropertiesCount' => count($topProperties),
            'domain' => $client->domain,
        ];

        return view('client.widget.analytics', compact(
            'stats', 'chartData', 'topLocations', 'topPropertyTypes', 'topProperties', 'allProperties', 'period', 'apiDown', 'debugInfo'
        ));
    }

    /**
     * Widget setup: license key, domain, WordPress instructions.
     */
    public function setup()
    {
        $client = auth()->user()->client;
        $client->load('licenseKeys');

        $licenseKey = $client->licenseKeys->first()?->key ?? '';
        $subscription = $client;
        $pluginVersion = '1.0.0';
        $settings = [
            'primary_color' => $client->primary_color ?? '#2563EB',
            'default_view'  => $client->default_view ?? 'grid',
            'per_page'      => $client->per_page ?? 24,
        ];

        return view('client.widget.setup', compact(
            'client', 'licenseKey', 'subscription', 'pluginVersion', 'settings'
        ));
    }

    /**
     * Inquiries captured from widget forms.
     */
    public function inquiryContacts(Request $request)
    {
        $client = auth()->user()->client;

        $query = Inquiry::where('client_id', $client->id)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('property_title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        $contacts = $query->paginate(25);

        // Map Inquiry fields to what the view expects (property_address is an accessor)
        $contacts->getCollection()->transform(function ($inquiry) {
            $inquiry->property_address = $inquiry->property_title;
            return $inquiry;
        });

        return view('client.widget.inquiry-contacts', compact('contacts'));
    }

    /**
     * Download the WordPress plugin ZIP.
     */
    public function downloadPlugin()
    {
        $pluginPath = resource_path('downloads/realtysoft-connector.zip');

        if (!file_exists($pluginPath)) {
            return back()->with('error', 'Plugin file not available. Please contact support.');
        }

        return response()->download($pluginPath, 'realtysoft-connector.zip');
    }

    /**
     * Update widget appearance settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'primary_color' => 'nullable|string|max:7',
            'default_view'  => 'nullable|in:grid,list,map',
            'per_page'      => 'nullable|in:12,24,36,48',
        ]);

        $client = auth()->user()->client;
        $client->update($request->only(['primary_color', 'default_view', 'per_page']));

        return back()->with('success', 'Widget settings updated.');
    }

    /**
     * Show widget configuration page.
     */
    public function config()
    {
        $client = auth()->user()->client;
        $config = $client->widget_config ?? [];

        return view('client.widget.config', compact('client', 'config'));
    }

    /**
     * Save widget configuration.
     */
    public function saveConfig(Request $request)
    {
        $request->validate([
            'enableAiSearch' => 'nullable',
            'enableMapView' => 'nullable',
            'enableCurrencyConverter' => 'nullable',
            'enableWishlist' => 'nullable',
            'baseCurrency' => 'nullable|string|max:3',
            'availableCurrencies' => 'nullable|array',
            'availableCurrencies.*' => 'string|max:3',
            'companyName' => 'nullable|string|max:255',
            'websiteUrl' => 'nullable|url|max:500',
            'logoUrl' => 'nullable|url|max:500',
            'primaryColor' => 'nullable|string|max:7',
            'emailHeaderColor' => 'nullable|string|max:7',
            'defaultView' => 'nullable|in:grid,list,map',
            'perPage' => 'nullable|in:12,24,36,48',
            'defaultLanguage' => 'nullable|string|max:5',
        ]);

        $client = auth()->user()->client;

        // Build widget_config array
        $config = $client->widget_config ?? [];

        // Feature toggles
        $config['enableAiSearch'] = $request->boolean('enableAiSearch');
        $config['enableMapView'] = $request->boolean('enableMapView');
        $config['enableCurrencyConverter'] = $request->boolean('enableCurrencyConverter');
        $config['enableWishlist'] = $request->boolean('enableWishlist');

        // Currency settings
        $config['baseCurrency'] = $request->input('baseCurrency', 'EUR');
        $config['availableCurrencies'] = $request->input('availableCurrencies', ['EUR', 'GBP', 'USD']);

        // Display settings
        $config['defaultView'] = $request->input('defaultView', 'grid');
        $config['perPage'] = (int) $request->input('perPage', 24);

        // Branding
        $config['branding'] = [
            'companyName' => $request->input('companyName'),
            'websiteUrl' => $request->input('websiteUrl'),
            'logoUrl' => $request->input('logoUrl'),
            'primaryColor' => $request->input('primaryColor'),
            'emailHeaderColor' => $request->input('emailHeaderColor'),
        ];

        $client->update([
            'widget_config' => $config,
            'ai_search_enabled' => $request->boolean('enableAiSearch'),
            'default_language' => $request->input('defaultLanguage', $client->default_language),
        ]);

        return back()->with('success', 'Widget configuration saved successfully.');
    }

    /**
     * Export inquiry contacts as CSV.
     */
    public function exportInquiryContacts()
    {
        $client = auth()->user()->client;
        $inquiries = Inquiry::where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->get();

        $csv = "Name,Email,Phone,Property,Message,Status,Date\n";
        foreach ($inquiries as $inquiry) {
            $csv .= implode(',', [
                '"' . str_replace('"', '""', $inquiry->name ?? '') . '"',
                '"' . str_replace('"', '""', $inquiry->email ?? '') . '"',
                '"' . str_replace('"', '""', $inquiry->phone ?? '') . '"',
                '"' . str_replace('"', '""', $inquiry->property_title ?? '') . '"',
                '"' . str_replace('"', '""', substr($inquiry->message ?? '', 0, 500)) . '"',
                '"' . ($inquiry->status ?? 'new') . '"',
                '"' . ($inquiry->created_at?->format('Y-m-d H:i') ?? '') . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inquiry-contacts.csv"',
        ]);
    }

    /**
     * Update inquiry status.
     */
    public function updateInquiryStatus(Request $request, Inquiry $inquiry)
    {
        $request->validate(['status' => 'required|in:new,contacted,converted,archived']);

        $inquiry->update(['status' => $request->status]);

        return back()->with('success', 'Inquiry status updated.');
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
