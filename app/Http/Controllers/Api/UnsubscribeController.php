<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Models\SuppressionList;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    public function show(string $token)
    {
        $contact = Contact::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('double_opt_in_token', $token)
            ->first();

        if (!$contact) {
            abort(404, 'Invalid unsubscribe link.');
        }

        return view('unsubscribe.show', compact('contact', 'token'));
    }

    public function process(string $token, Request $request)
    {
        $contact = Contact::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('double_opt_in_token', $token)
            ->first();

        if (!$contact) {
            abort(404, 'Invalid unsubscribe link.');
        }

        $contact->update(['status' => 'unsubscribed']);

        SuppressionList::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->updateOrCreate(
                ['client_id' => $contact->client_id, 'email' => $contact->email],
                ['reason' => 'unsubscribed']
            );

        EmailEvent::create([
            'client_id'        => $contact->client_id,
            'campaign_send_id' => $request->input('send_id', 0),
            'campaign_id'      => $request->input('campaign_id', 0),
            'contact_id'       => $contact->id,
            'event_type'       => 'unsubscribe',
            'ip_address'       => $request->ip(),
        ]);

        return view('unsubscribe.confirmed', compact('contact'));
    }
}
