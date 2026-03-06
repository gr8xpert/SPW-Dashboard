<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contact;
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

        return view('client.widget.index', compact('client', 'subscriptionInfo'));
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
     * Contacts captured from widget inquiries.
     */
    public function inquiryContacts(Request $request)
    {
        $client = auth()->user()->client;

        $query = Contact::where('client_id', $client->id)
            ->where('source', 'widget_inquiry')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate(25);

        // Map Contact fields to what the view expects
        $contacts->getCollection()->transform(function ($contact) {
            $contact->name = trim($contact->first_name . ' ' . $contact->last_name);
            $customFields = json_decode($contact->custom_fields, true) ?? [];
            $contact->property_address = $customFields['last_inquiry_title'] ?? null;
            $contact->message = $customFields['last_inquiry_message'] ?? null;
            return $contact;
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
        $contacts = Contact::where('client_id', $client->id)
            ->where('source', 'widget_inquiry')
            ->orderByDesc('created_at')
            ->get();

        $csv = "Name,Email,Phone,Property,Status,Date\n";
        foreach ($contacts as $contact) {
            $name = trim($contact->first_name . ' ' . $contact->last_name);
            $customFields = json_decode($contact->custom_fields, true) ?? [];
            $csv .= implode(',', [
                '"' . str_replace('"', '""', $name) . '"',
                '"' . str_replace('"', '""', $contact->email ?? '') . '"',
                '"' . str_replace('"', '""', $contact->phone ?? '') . '"',
                '"' . str_replace('"', '""', $customFields['last_inquiry_title'] ?? '') . '"',
                '"' . ($contact->status ?? 'new') . '"',
                '"' . ($contact->created_at?->format('Y-m-d H:i') ?? '') . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inquiry-contacts.csv"',
        ]);
    }

    /**
     * Update inquiry contact status.
     */
    public function updateInquiryStatus(Request $request, Contact $contact)
    {
        $request->validate(['status' => 'required|in:new,contacted,converted,archived']);

        $contact->update(['status' => $request->status]);

        return back()->with('success', 'Contact status updated.');
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
