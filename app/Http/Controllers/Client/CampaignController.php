<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\ContactList;
use App\Models\Template;
use App\Models\SmtpAccount;
use App\Models\CampaignSend;
use App\Models\EmailEvent;
use App\Services\CampaignSendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $campaigns = $query->latest()->paginate(20)->withQueryString();

        // Optimized: Single query with groupBy instead of 4 separate count queries
        $statusCounts = Campaign::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $stats = [
            'total'     => $statusCounts->sum(),
            'sent'      => $statusCounts->get('sent', 0),
            'draft'     => $statusCounts->get('draft', 0),
            'scheduled' => $statusCounts->get('scheduled', 0),
        ];

        return view('client.campaigns.index', compact('campaigns', 'stats'));
    }

    public function create()
    {
        $lists        = ContactList::withCount('contacts')->orderBy('name')->get();
        $templates    = Template::orderBy('name')->get();
        $smtpAccounts = SmtpAccount::orderBy('name')->get();

        return view('client.campaigns.create', compact('lists', 'templates', 'smtpAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:200',
            'subject'         => 'required|string|max:500',
            'from_name'       => 'required|string|max:200',
            'from_email'      => 'required|email',
            'reply_to'        => 'nullable|email',
            'list_id'         => 'nullable|exists:contact_lists,id',
            'template_id'     => 'nullable|exists:templates,id',
            'html_body'       => 'nullable|string',
            'plain_text'      => 'nullable|string',
            'smtp_account_id' => 'nullable|exists:smtp_accounts,id',
        ]);

        // Map html_body/plain_text to DB column names
        if (isset($data['html_body'])) {
            $data['html_content'] = $data['html_body'];
            unset($data['html_body']);
        }
        if (isset($data['plain_text'])) {
            $data['plain_text_content'] = $data['plain_text'];
            unset($data['plain_text']);
        }

        $data['status'] = 'draft';
        $campaign = Campaign::create($data);

        return redirect()->route('dashboard.campaigns.edit', $campaign)
            ->with('success', 'Campaign created. Review and send when ready.');
    }

    public function show(Campaign $campaign)
    {
        // Optimized: Single query for send counts
        $sendStats = CampaignSend::where('campaign_id', $campaign->id)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN sent_at IS NOT NULL THEN 1 ELSE 0 END) as delivered')
            ->first();

        $sent      = $sendStats->total ?? 0;
        $delivered = $sendStats->delivered ?? 0;

        // Optimized: Single query for all event type counts
        $eventCounts = EmailEvent::where('campaign_id', $campaign->id)
            ->selectRaw('event_type, COUNT(DISTINCT contact_id) as unique_count, COUNT(*) as total_count')
            ->groupBy('event_type')
            ->pluck('unique_count', 'event_type');

        $opens        = $eventCounts->get('open', 0);
        $clicks       = $eventCounts->get('click', 0);
        $bounces      = $eventCounts->get('bounce', 0);
        $unsubscribes = $eventCounts->get('unsubscribe', 0);

        $stats = [
            'sent'         => $sent,
            'delivered'    => $delivered,
            'opens'        => $opens,
            'clicks'       => $clicks,
            'bounces'      => $bounces,
            'unsubscribes' => $unsubscribes,
            'open_rate'    => $delivered > 0 ? round($opens  / $delivered * 100, 1) : 0,
            'click_rate'   => $delivered > 0 ? round($clicks / $delivered * 100, 1) : 0,
        ];

        $recentEvents = EmailEvent::where('campaign_id', $campaign->id)
            ->with('contact')
            ->latest()
            ->limit(50)
            ->get();

        return view('client.campaigns.show', compact('campaign', 'stats', 'recentEvents'));
    }

    public function edit(Campaign $campaign)
    {
        $lists        = ContactList::withCount('contacts')->orderBy('name')->get();
        $templates    = Template::orderBy('name')->get();
        $smtpAccounts = SmtpAccount::orderBy('name')->get();

        return view('client.campaigns.edit', compact('campaign', 'lists', 'templates', 'smtpAccounts'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:200',
            'subject'         => 'required|string|max:500',
            'from_name'       => 'required|string|max:200',
            'from_email'      => 'required|email',
            'reply_to'        => 'nullable|email',
            'list_id'         => 'nullable|exists:contact_lists,id',
            'template_id'     => 'nullable|exists:templates,id',
            'html_body'       => 'nullable|string',
            'plain_text'      => 'nullable|string',
            'smtp_account_id' => 'nullable|exists:smtp_accounts,id',
        ]);

        if (isset($data['html_body'])) {
            $data['html_content'] = $data['html_body'];
            unset($data['html_body']);
        }
        if (isset($data['plain_text'])) {
            $data['plain_text_content'] = $data['plain_text'];
            unset($data['plain_text']);
        }

        $campaign->update($data);

        return redirect()->route('dashboard.campaigns.edit', $campaign)
            ->with('success', 'Campaign saved.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('dashboard.campaigns.index')
            ->with('success', 'Campaign deleted.');
    }

    public function schedule(Request $request, Campaign $campaign)
    {
        $request->validate(['scheduled_at' => 'required|date|after:now']);

        $campaign->update([
            'status'       => 'scheduled',
            'scheduled_at' => $request->scheduled_at,
        ]);

        return redirect()->route('dashboard.campaigns.show', $campaign)
            ->with('success', 'Campaign scheduled for ' . $request->scheduled_at);
    }

    public function sendNow(Request $request, Campaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', 'Campaign cannot be sent in its current state.');
        }

        try {
            app(CampaignSendService::class)->dispatch($campaign);
        } catch (\Throwable $e) {
            // Log the actual error but don't expose details to user
            \Illuminate\Support\Facades\Log::error('Campaign send failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('dashboard.campaigns.show', $campaign)
                ->with('error', 'Send failed. Please check your SMTP settings or contact support.');
        }

        return redirect()->route('dashboard.campaigns.show', $campaign)
            ->with('success', 'Campaign is now sending!');
    }

    public function pause(Campaign $campaign)
    {
        $campaign->update(['status' => 'paused']);

        return back()->with('success', 'Campaign paused.');
    }

    public function cancel(Campaign $campaign)
    {
        $campaign->update(['status' => 'cancelled']);

        return back()->with('success', 'Campaign cancelled.');
    }

    public function testSend(Request $request, Campaign $campaign)
    {
        $request->validate(['test_email' => 'required|email']);

        try {
            app(CampaignSendService::class)->sendTest($campaign, $request->test_email);
            return back()->with('success', 'Test email sent to ' . $request->test_email);
        } catch (\Throwable $e) {
            // Log the actual error but don't expose internal details to user
            \Illuminate\Support\Facades\Log::error('Campaign test send failed', [
                'campaign_id' => $campaign->id,
                'test_email' => $request->test_email,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Test send failed. Please check your SMTP configuration.');
        }
    }

    public function preview(Campaign $campaign)
    {
        return response($campaign->html_content ?? '<p>No content</p>');
    }

    public function stats(Campaign $campaign)
    {
        return redirect()->route('dashboard.campaigns.show', $campaign);
    }
}
