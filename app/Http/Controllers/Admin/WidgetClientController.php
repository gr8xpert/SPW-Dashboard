<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Plan;
use App\Services\WidgetSubscriptionService;
use Illuminate\Http\Request;

class WidgetClientController extends Controller
{
    public function __construct(
        protected WidgetSubscriptionService $subscriptionService
    ) {}

    public function index(Request $request)
    {
        $query = Client::with('plan')
            ->withCount('supportTickets');

        if ($request->filled('status')) {
            $query->where('subscription_status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('company_name', 'like', "%{$request->search}%")
                    ->orWhere('domain', 'like', "%{$request->search}%");
            });
        }

        $clients = $query->orderByDesc('updated_at')->paginate(25);

        return view('admin.widget-clients.index', compact('clients'));
    }

    public function edit(Client $client)
    {
        $client->load(['plan', 'domains', 'licenseKeys']);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.widget-clients.edit', compact('client', 'plans'));
    }

    public function update(Request $request, Client $client)
    {
        $request->validate([
            'domain'              => 'nullable|string|max:255',
            'api_url'             => 'nullable|url|max:500',
            'api_key'             => 'nullable|string|max:255',
            'owner_email'         => 'nullable|email|max:255',
            'default_language'    => 'nullable|string|max:10',
            'ai_search_enabled'   => 'boolean',
            'widget_enabled'      => 'boolean',
            'admin_override'      => 'boolean',
            'is_internal'         => 'boolean',
            'billing_source'      => 'in:paddle,manual,internal',
            'subscription_status' => 'in:active,grace,expired,manual,internal',
        ]);

        $oldValues = $client->only([
            'domain', 'widget_enabled', 'admin_override',
            'is_internal', 'billing_source', 'subscription_status',
        ]);

        $client->update($request->only([
            'domain', 'api_url', 'api_key', 'owner_email', 'default_language',
            'ai_search_enabled', 'widget_enabled', 'admin_override',
            'is_internal', 'billing_source', 'subscription_status',
        ]));

        AuditLog::log(
            'widget_client.updated',
            'client',
            $client->id,
            ['changes' => $request->all()],
            $oldValues,
            $client->fresh()->only(array_keys($oldValues))
        );

        return redirect()->route('admin.widget-clients.edit', $client)
            ->with('success', 'Widget client updated successfully.');
    }

    public function toggleOverride(Client $client)
    {
        $newState = !$client->admin_override;
        $this->subscriptionService->setAdminOverride($client->id, $newState);

        AuditLog::log('widget_client.override_toggled', 'client', $client->id, [
            'admin_override' => $newState,
        ]);

        return back()->with('success', $newState
            ? 'Admin override enabled — widget will always be active.'
            : 'Admin override disabled — billing status will be enforced.');
    }

    public function extendSubscription(Request $request, Client $client)
    {
        $request->validate(['period' => 'required|string|in:1 month,3 months,6 months,1 year']);

        $this->subscriptionService->extendSubscription($client->id, $request->period);

        AuditLog::log('widget_client.subscription_extended', 'client', $client->id, [
            'period' => $request->period,
        ]);

        return back()->with('success', "Subscription extended by {$request->period}.");
    }

    public function manualActivate(Client $client)
    {
        $this->subscriptionService->manualActivate($client->id);

        AuditLog::log('widget_client.manually_activated', 'client', $client->id);

        return back()->with('success', 'Client manually activated.');
    }

    public function expire(Client $client)
    {
        $this->subscriptionService->expireSubscription($client->id);

        AuditLog::log('widget_client.manually_expired', 'client', $client->id);

        return back()->with('success', 'Client subscription expired.');
    }

    public function subscriptionStatus()
    {
        $active = Client::where('subscription_status', 'active')->count();
        $grace = Client::where('subscription_status', 'grace')->count();
        $expired = Client::where('subscription_status', 'expired')->count();
        $manual = Client::where('subscription_status', 'manual')->count();
        $internal = Client::where('subscription_status', 'internal')->count();
        $overridden = Client::where('admin_override', true)->count();

        $graceClients = Client::where('subscription_status', 'grace')
            ->with('plan')
            ->orderBy('grace_ends_at')
            ->get();

        return view('admin.widget-clients.subscription-status', compact(
            'active', 'grace', 'expired', 'manual', 'internal', 'overridden', 'graceClients'
        ));
    }
}
