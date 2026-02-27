<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactList;
use App\Services\AutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\Csv\Reader;
use League\Csv\Writer;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('list')) {
            $query->whereHas('lists', fn($q) => $q->where('contact_lists.id', $request->list));
        }

        $contacts = $query->latest()->paginate(50)->withQueryString();
        $lists = ContactList::orderBy('name')->get();

        // Optimized: Single query for all status counts instead of 3 separate queries
        $statusCounts = Contact::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $total = $statusCounts->sum();
        $subscribed = $statusCounts->get('subscribed', 0);
        $unsubscribed = $statusCounts->get('unsubscribed', 0);

        return view('client.contacts.index', compact('contacts', 'lists', 'total', 'subscribed', 'unsubscribed'));
    }

    public function create()
    {
        $lists = ContactList::orderBy('name')->get();
        return view('client.contacts.create', compact('lists'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email'      => 'required|email|max:320',
            'first_name' => 'nullable|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'phone'      => 'nullable|string|max:30',
            'company'    => 'nullable|string|max:200',
            'status'     => 'required|in:subscribed,unsubscribed,bounced,complained',
            'tags'       => 'nullable|string',
            'lists'      => 'nullable|array',
        ]);

        $data['tags'] = $data['tags'] ? array_map('trim', explode(',', $data['tags'])) : [];
        $lists = $data['lists'] ?? [];
        unset($data['lists']);

        $contact = Contact::create($data);

        if ($lists) {
            $contact->lists()->attach($lists, ['added_at' => now()]);
        }

        // Fire contact_added automations
        try {
            app(AutomationService::class)->checkAndFire('contact_added', $contact);
        } catch (\Throwable $e) {
            // Automation errors must not prevent contact creation, but we log them
            \Illuminate\Support\Facades\Log::warning('Automation failed for contact_added', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('dashboard.contacts.index')
            ->with('success', 'Contact added successfully.');
    }

    public function show(Contact $contact)
    {
        $contact->load('lists');
        $emailHistory = $contact->emailEvents()->with('campaign')->latest()->limit(20)->get();
        return view('client.contacts.show', compact('contact', 'emailHistory'));
    }

    public function edit(Contact $contact)
    {
        $lists = ContactList::orderBy('name')->get();
        $contactListIds = $contact->lists->pluck('id')->toArray();
        return view('client.contacts.edit', compact('contact', 'lists', 'contactListIds'));
    }

    public function update(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'email'      => 'required|email|max:320',
            'first_name' => 'nullable|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'phone'      => 'nullable|string|max:30',
            'company'    => 'nullable|string|max:200',
            'status'     => 'required|in:subscribed,unsubscribed,bounced,complained',
            'tags'       => 'nullable|string',
            'lists'      => 'nullable|array',
        ]);

        $data['tags'] = $data['tags'] ? array_map('trim', explode(',', $data['tags'])) : [];
        $lists = $data['lists'] ?? [];
        unset($data['lists']);

        $contact->update($data);
        $contact->lists()->sync($lists ? array_fill_keys($lists, ['added_at' => now(), 'client_id' => $contact->client_id]) : []);

        return redirect()->route('dashboard.contacts.index')
            ->with('success', 'Contact updated.');
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        return redirect()->route('dashboard.contacts.index')
            ->with('success', 'Contact deleted.');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:10240']);

        $csv = Reader::createFromPath($request->file('file')->getPathname(), 'r');
        $csv->setHeaderOffset(0);

        $imported = 0;
        $skipped  = 0;
        $clientId = Auth::user()->client_id;

        foreach ($csv->getRecords() as $row) {
            // Normalize keys: lowercase + strip BOM and whitespace
            $normalized = [];
            foreach ($row as $key => $value) {
                $cleanKey = strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $key)));
                $normalized[$cleanKey] = $value;
            }

            $email = trim($normalized['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            Contact::updateOrCreate(
                ['client_id' => $clientId, 'email' => $email],
                [
                    'client_id'  => $clientId,
                    'first_name' => $normalized['first_name'] ?? null,
                    'last_name'  => $normalized['last_name']  ?? null,
                    'phone'      => $normalized['phone']      ?? null,
                    'company'    => $normalized['company']    ?? null,
                    'status'     => 'subscribed',
                ]
            );
            $imported++;
        }

        return redirect()->route('dashboard.contacts.index')
            ->with('success', "Imported {$imported} contacts. Skipped {$skipped} invalid rows.");
    }

    public function export(Request $request)
    {
        // Explicit client_id filter — safe even during admin impersonation
        $contacts = Contact::where('client_id', Auth::user()->client_id)->get();

        $csv = Writer::createFromString();
        $csv->insertOne(['email','first_name','last_name','phone','company','status','tags','created_at']);

        foreach ($contacts as $c) {
            $csv->insertOne([
                $c->email, $c->first_name, $c->last_name, $c->phone, $c->company,
                $c->status, implode(',', $c->tags ?? []), $c->created_at->toDateString(),
            ]);
        }

        return response($csv->toString())
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="contacts-' . now()->format('Y-m-d') . '.csv"');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action'  => 'required|in:delete,unsubscribe,subscribe,add_to_list',
            'ids'     => 'required|array',
            'list_id' => 'nullable|integer',
        ]);

        $ids = $request->ids;

        switch ($request->action) {
            case 'delete':
                Contact::whereIn('id', $ids)->delete();
                $msg = count($ids) . ' contacts deleted.';
                break;
            case 'unsubscribe':
                Contact::whereIn('id', $ids)->update(['status' => 'unsubscribed']);
                $msg = count($ids) . ' contacts unsubscribed.';
                break;
            case 'subscribe':
                Contact::whereIn('id', $ids)->update(['status' => 'subscribed']);
                $msg = count($ids) . ' contacts subscribed.';
                break;
            case 'add_to_list':
                $list = ContactList::findOrFail($request->list_id);
                $contacts = Contact::whereIn('id', $ids)->get();
                foreach ($contacts as $contact) {
                    $contact->lists()->syncWithoutDetaching([$request->list_id => ['added_at' => now(), 'client_id' => $contact->client_id]]);
                    // Fire list_subscribed automations
                    try {
                        app(AutomationService::class)->checkAndFire('list_subscribed', $contact, ['list_id' => $request->list_id]);
                    } catch (\Throwable $e) {
                        // Automation errors must not interrupt bulk actions, but we log them
                        \Illuminate\Support\Facades\Log::warning('Automation failed for list_subscribed', [
                            'contact_id' => $contact->id,
                            'list_id' => $request->list_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                $msg = count($ids) . ' contacts added to list.';
                break;
            default:
                $msg = 'Unknown action.';
        }

        return redirect()->route('dashboard.contacts.index')->with('success', $msg);
    }
}
