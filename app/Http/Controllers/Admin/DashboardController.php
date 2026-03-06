<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientUsage;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_clients'   => Client::count(),
            'trial_clients'   => Client::where('status', 'trial')->count(),
            'active_clients'  => Client::where('status', 'active')->count(),
            'suspended'       => Client::where('status', 'suspended')->count(),
            'total_users'     => User::withoutGlobalScope(\App\Scopes\TenantScope::class)->count(),
        ];

        // Widget-specific stats
        $stats['widget_clients'] = Client::whereNotNull('domain')->count();
        $stats['widget_active'] = Client::whereNotNull('domain')
            ->whereIn('subscription_status', ['active', 'manual', 'internal'])
            ->orWhere('admin_override', true)
            ->count();
        $stats['widget_grace'] = Client::whereNotNull('domain')
            ->where('subscription_status', 'grace')
            ->count();
        $stats['widget_expired'] = Client::whereNotNull('domain')
            ->where('subscription_status', 'expired')
            ->count();

        // Clients expiring in next 7 days
        $stats['expiring_soon'] = Client::whereNotNull('domain')
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<=', now()->addDays(7))
            ->where('subscription_expires_at', '>', now())
            ->where('admin_override', false)
            ->count();

        // Clients with Resales API configured
        $stats['resales_configured'] = Client::whereNotNull('resales_client_id')
            ->whereNotNull('resales_api_key')
            ->count();

        $month = now()->format('Y-m');
        $stats['emails_this_month'] = ClientUsage::where('month', $month)->sum('emails_sent');

        // MRR calculation: monthly clients use price_monthly, yearly clients use price_yearly/12
        // Include clients with active subscription_status OR active/trial status OR admin_override
        $stats['mrr'] = Client::where(function ($q) {
                $q->whereIn('subscription_status', ['active', 'grace', 'manual', 'internal'])
                  ->orWhereIn('status', ['active', 'trial'])
                  ->orWhere('admin_override', true);
            })
            ->whereNotNull('plan_id')
            ->join('plans', 'clients.plan_id', '=', 'plans.id')
            ->selectRaw("SUM(CASE
                WHEN clients.billing_cycle = 'yearly' THEN plans.price_yearly / 12
                ELSE plans.price_monthly
            END) as mrr")
            ->value('mrr') ?? 0;

        // New signups last 30 days
        $stats['new_signups'] = Client::where('created_at', '>=', now()->subDays(30))->count();

        // Emails per day last 30 days (raw DB query — no global scopes apply)
        $emailsPerDay = DB::table('campaign_sends')
            ->selectRaw('DATE(sent_at) as date, COUNT(*) as count')
            ->where('sent_at', '>=', now()->subDays(30))
            ->whereNotNull('sent_at')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Recent clients
        $recentClients = Client::with('plan')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'emailsPerDay', 'recentClients'));
    }
}
