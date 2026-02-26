<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::with('plan');

        if ($request->search) {
            $query->where('company_name', 'like', "%{$request->search}%");
        }
        if ($request->plan) {
            $query->where('plan_id', $request->plan);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $clients = $query->latest()->paginate(25);
        $plans   = Plan::all();

        return view('admin.clients.index', compact('clients', 'plans'));
    }

    public function create()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return view('admin.clients.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name'        => 'required|string|max:255',
            'domain'              => 'required|string|max:255',
            'owner_email'         => 'required|email|max:255',
            'plan_id'             => 'required|exists:plans,id',
            'api_url'             => 'nullable|url|max:500',
            'default_language'    => 'nullable|string|max:10',
            'subscription_status' => 'required|in:active,manual,internal',
            'billing_source'      => 'required|in:paddle,manual,internal',
            'admin_password'      => 'required|string|min:8',
        ]);

        $client = DB::transaction(function () use ($request) {
            $client = Client::create([
                'company_name'        => $request->company_name,
                'domain'              => $request->domain,
                'owner_email'         => $request->owner_email,
                'plan_id'             => $request->plan_id,
                'api_url'             => $request->api_url,
                'default_language'    => $request->default_language ?? 'en',
                'subscription_status' => $request->subscription_status,
                'billing_source'      => $request->billing_source,
                'widget_enabled'      => true,
                'admin_override'      => $request->boolean('admin_override'),
                'is_internal'         => $request->billing_source === 'internal',
                'status'              => 'active',
            ]);

            // Create an admin user for this client
            User::create([
                'name'      => $request->company_name . ' Admin',
                'email'     => $request->owner_email,
                'password'  => Hash::make($request->admin_password),
                'client_id' => $client->id,
                'role'      => 'admin',
            ]);

            // Auto-generate a license key
            LicenseKey::create([
                'client_id'        => $client->id,
                'plan_id'          => $client->plan_id,
                'status'           => 'activated',
                'activated_at'     => now(),
                'activated_domain' => $client->domain,
            ]);

            return $client;
        });

        return redirect()->route('admin.widget-clients.edit', $client)
            ->with('success', "Client '{$client->company_name}' created successfully with license key.");
    }

    public function show(Client $client)
    {
        $client->load('plan', 'users');
        $usage = $client->getCurrentUsage();
        $auditLogs = \App\Models\AuditLog::forClient($client->id)
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.clients.show', compact('client', 'usage', 'auditLogs'));
    }

    public function suspend(Client $client)
    {
        $client->update(['status' => 'suspended']);
        return back()->with('success', "Client {$client->company_name} has been suspended.");
    }

    public function activate(Client $client)
    {
        $client->update(['status' => 'active']);
        return back()->with('success', "Client {$client->company_name} has been activated.");
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('admin.clients.index')
            ->with('success', 'Client deleted successfully.');
    }

    public function impersonate(Client $client)
    {
        $adminUser = Auth::user();
        $clientAdmin = $client->users()->where('role', 'admin')->first();

        if (!$clientAdmin) {
            return back()->with('error', 'No admin user found for this client.');
        }

        session(['impersonating_as' => $clientAdmin->id, 'impersonator_id' => $adminUser->id]);
        Auth::login($clientAdmin);

        return redirect()->route('dashboard.home')->with('info', "You are now impersonating {$client->company_name}");
    }

    public function stopImpersonating(Request $request)
    {
        $impersonatorId = $request->session()->get('impersonator_id');
        if ($impersonatorId) {
            $admin = \App\Models\User::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->find($impersonatorId);
            Auth::login($admin);
            $request->session()->forget(['impersonating_as', 'impersonator_id']);
        }
        return redirect()->route('admin.dashboard');
    }
}
