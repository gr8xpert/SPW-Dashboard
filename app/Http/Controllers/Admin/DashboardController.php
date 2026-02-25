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

        $stats['mrr'] = Client::whereIn('status', ['active', 'trial'])
            ->join('plans', 'clients.plan_id', '=', 'plans.id')
            ->sum('plans.price_monthly');

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
