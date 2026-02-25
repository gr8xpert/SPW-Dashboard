<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Http\Request;

class ContactApiController extends Controller
{
    public function index(Request $request)
    {
        $client = $request->get('_api_client');
        $contacts = Contact::forClient($client->id)
            ->paginate(100);
        return response()->json($contacts);
    }

    public function store(Request $request)
    {
        $client = $request->get('_api_client');
        $data = $request->validate([
            'email'      => 'required|email',
            'first_name' => 'sometimes|string|max:100',
            'last_name'  => 'sometimes|string|max:100',
            'tags'       => 'sometimes|array',
        ]);

        $contact = Contact::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->updateOrCreate(
                ['email' => $data['email'], 'client_id' => $client->id],
                array_merge($data, ['client_id' => $client->id])
            );

        return response()->json($contact, 201);
    }

    public function show(int $id, Request $request)
    {
        $client = $request->get('_api_client');
        $contact = Contact::forClient($client->id)->findOrFail($id);
        return response()->json($contact);
    }

    public function update(int $id, Request $request)
    {
        $client = $request->get('_api_client');
        $contact = Contact::forClient($client->id)->findOrFail($id);
        $contact->update($request->only(['first_name', 'last_name', 'phone', 'company', 'tags', 'custom_fields']));
        return response()->json($contact);
    }

    public function destroy(int $id, Request $request)
    {
        $client = $request->get('_api_client');
        Contact::forClient($client->id)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function lists(Request $request)
    {
        $client = $request->get('_api_client');
        return response()->json(ContactList::forClient($client->id)->get());
    }

    public function addToList(int $listId, Request $request)
    {
        $client = $request->get('_api_client');
        $request->validate(['email' => 'required|email']);

        $list    = ContactList::forClient($client->id)->findOrFail($listId);
        $contact = Contact::forClient($client->id)->where('email', $request->email)->first();

        if (!$contact) {
            return response()->json(['error' => 'Contact not found'], 404);
        }

        $list->contacts()->syncWithoutDetaching([$contact->id]);
        return response()->json(['success' => true]);
    }

    public function addTags(Request $request)
    {
        $client = $request->get('_api_client');
        $request->validate(['emails' => 'required|array', 'tags' => 'required|array']);

        Contact::forClient($client->id)
            ->whereIn('email', $request->emails)
            ->each(function ($contact) use ($request) {
                $tags = array_unique(array_merge($contact->tags ?? [], $request->tags));
                $contact->update(['tags' => $tags]);
            });

        return response()->json(['success' => true]);
    }
}
