<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Http\Request;

class ListController extends Controller
{
    public function index()
    {
        $lists = ContactList::withCount('contacts')->latest()->paginate(20);
        return view('client.lists.index', compact('lists'));
    }

    public function create()
    {
        return view('client.lists.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);

        ContactList::create($data);

        return redirect()->route('dashboard.lists.index')
            ->with('success', 'List created.');
    }

    public function show(ContactList $list)
    {
        $contacts = $list->contacts()->paginate(50);
        return view('client.lists.show', compact('list', 'contacts'));
    }

    public function edit(ContactList $list)
    {
        return view('client.lists.edit', compact('list'));
    }

    public function update(Request $request, ContactList $list)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);

        $list->update($data);

        return redirect()->route('dashboard.lists.index')
            ->with('success', 'List updated.');
    }

    public function destroy(ContactList $list)
    {
        $list->delete();
        return redirect()->route('dashboard.lists.index')
            ->with('success', 'List deleted.');
    }
}
