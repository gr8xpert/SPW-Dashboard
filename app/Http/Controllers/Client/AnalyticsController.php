<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Models\CampaignSend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $totalSent    = CampaignSend::whereNotNull('sent_at')->count();
        $totalOpens   = EmailEvent::where('event_type', 'open')->count();
        $totalClicks  = EmailEvent::where('event_type', 'click')->count();
        $totalBounces = EmailEvent::where('event_type', 'bounce')->count();
        $totalUnsubs  = EmailEvent::where('event_type', 'unsubscribe')->count();

        $openRate  = $totalSent > 0 ? round($totalOpens  / $totalSent * 100, 1) : 0;
        $clickRate = $totalSent > 0 ? round($totalClicks / $totalSent * 100, 1) : 0;

        // Opens per day — last 30 days
        $opensPerDay = EmailEvent::where('event_type', 'open')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $clicksPerDay = EmailEvent::where('event_type', 'click')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels    = [];
        $openData  = [];
        $clickData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date        = now()->subDays($i)->format('Y-m-d');
            $labels[]    = now()->subDays($i)->format('M d');
            $openData[]  = $opensPerDay[$date]->count  ?? 0;
            $clickData[] = $clicksPerDay[$date]->count ?? 0;
        }

        $topCampaigns = Campaign::withCount([
                'sends as sent_count' => fn ($q) => $q->whereNotNull('sent_at'),
            ])
            ->where('status', 'sent')
            ->latest('completed_at')
            ->limit(10)
            ->get();

        return view('client.analytics.index', compact(
            'totalSent', 'totalOpens', 'totalClicks', 'totalBounces', 'totalUnsubs',
            'openRate', 'clickRate', 'labels', 'openData', 'clickData', 'topCampaigns'
        ));
    }

    public function campaigns()
    {
        $campaigns = Campaign::where('status', 'sent')
            ->withCount([
                'sends as sent_count'     => fn ($q) => $q->whereNotNull('sent_at'),
                'events as opens_count'   => fn ($q) => $q->where('event_type', 'open'),
                'events as clicks_count'  => fn ($q) => $q->where('event_type', 'click'),
                'events as bounces_count' => fn ($q) => $q->where('event_type', 'bounce'),
                'events as unsubs_count'  => fn ($q) => $q->where('event_type', 'unsubscribe'),
            ])
            ->latest('completed_at')
            ->paginate(20);

        return view('client.analytics.campaigns', compact('campaigns'));
    }

    public function contacts()
    {
        $topEngaged = Contact::where('status', 'subscribed')
            ->where('engagement_tier', 'hot')
            ->orderByDesc('lead_score')
            ->limit(50)
            ->get();

        $byStatus = Contact::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return view('client.analytics.contacts', compact('topEngaged', 'byStatus'));
    }
}
