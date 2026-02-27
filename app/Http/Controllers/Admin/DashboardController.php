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
