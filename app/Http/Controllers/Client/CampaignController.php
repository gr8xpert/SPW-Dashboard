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

        $stats = [
            'total'     => Campaign::count(),
            'sent'      => Campaign::where('status', 'sent')->count(),
            'draft'     => Campaign::where('status', 'draft')->count(),
            'scheduled' => Campaign::where('status', 'scheduled')->count(),
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
        $sent      = CampaignSend::where('campaign_id', $campaign->id)->count();
        $delivered = CampaignSend::where('campaign_id', $campaign->id)->whereNotNull('sent_at')->count();
        $opens     = EmailEvent::where('campaign_id', $campaign->id)->where('event_type', 'open')->distinct('contact_id')->count();
        $clicks    = EmailEvent::where('campaign_id', $campaign->id)->where('event_type', 'click')->distinct('contact_id')->count();

        $stats = [
            'sent'         => $sent,
            'delivered'    => $delivered,
            'opens'        => $opens,
            'clicks'       => $clicks,
            'bounces'      => EmailEvent::where('campaign_id', $campaign->id)->where('event_type', 'bounce')->count(),
            'unsubscribes' => EmailEvent::where('campaign_id', $campaign->id)->where('event_type', 'unsubscribe')->count(),
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
            return redirect()->route('dashboard.campaigns.show', $campaign)
                ->with('error', 'Send failed: ' . $e->getMessage());
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
            return back()->with('error', 'Test send failed: ' . $e->getMessage());
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
