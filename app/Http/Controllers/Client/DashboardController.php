<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ClientUsage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $client = Auth::user()->client;
        $cacheKey = "dashboard_stats:{$client->id}";

        $stats = Cache::remember($cacheKey, 300, function () use ($client) {
            $month = now()->format('Y-m');
            $usage = ClientUsage::firstOrCreate(
                ['client_id' => $client->id, 'month' => $month],
                ['emails_sent' => 0, 'contacts_count' => 0]
            );

            $totalContacts = Contact::where('status', 'subscribed')->count();

            $avgOpenRate = Campaign::where('status', 'sent')
                ->where('total_sent', '>', 0)
                ->selectRaw('AVG(total_opened / total_sent * 100) as avg_open_rate')
                ->value('avg_open_rate');

            return [
                'total_contacts'     => $totalContacts,
                'emails_sent'        => $usage->emails_sent,
                'emails_limit'       => $client->plan->max_emails_per_month,
                'avg_open_rate'      => round($avgOpenRate ?? 0, 1),
                'total_campaigns'    => Campaign::count(),
                'sending_campaigns'  => Campaign::whereIn('status', ['sending', 'queued'])->count(),
            ];
        });

        $recentCampaigns = Campaign::with('list')
            ->latest()
            ->limit(5)
            ->get();

        // Engagement chart data (last 30 days)
        $chartData = $this->getEngagementChartData();

        return view('client.dashboard', compact('stats', 'recentCampaigns', 'chartData', 'client'));
    }

    protected function getEngagementChartData(): array
    {
        $client = Auth::user()->client;
        $days = collect();

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $days->push($date);
        }

        $opens = \App\Models\EmailEvent::where('event_type', 'open')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $clicks = \App\Models\EmailEvent::where('event_type', 'click')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        return [
            'labels' => $days->toArray(),
            'opens'  => $days->map(fn($d) => $opens[$d] ?? 0)->toArray(),
            'clicks' => $days->map(fn($d) => $clicks[$d] ?? 0)->toArray(),
        ];
    }
}
