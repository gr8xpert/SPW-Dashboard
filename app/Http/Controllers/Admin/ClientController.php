<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
