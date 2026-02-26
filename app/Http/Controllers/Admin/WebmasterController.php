<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class WebmasterController extends Controller
{
    public function index()
    {
        $webmasters = User::where('role', 'webmaster')
            ->withCount(['assignedTickets', 'assignedTickets as open_tickets_count' => function ($q) {
                $q->whereIn('status', ['assigned', 'in_progress', 'review']);
            }])
            ->paginate(25);

        return view('admin.webmasters.index', compact('webmasters'));
    }

    public function create()
    {
        return view('admin.webmasters.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|max:320|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        // Webmasters belong to the super admin's client (or a dedicated system client)
        $adminClient = auth()->user()->client;

        $user = User::create([
            'client_id' => $adminClient->id,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => 'webmaster',
            'status'    => 'active',
        ]);

        AuditLog::log('webmaster.created', 'user', $user->id);

        return redirect()->route('admin.webmasters.index')
            ->with('success', 'Webmaster account created.');
    }

    public function destroy(User $webmaster)
    {
        if ($webmaster->role !== 'webmaster') {
            return back()->with('error', 'This user is not a webmaster.');
        }

        // Unassign all tickets first
        $webmaster->assignedTickets()->update(['assigned_to' => null, 'status' => 'open']);

        $webmaster->delete();

        AuditLog::log('webmaster.deleted', 'user', $webmaster->id);

        return redirect()->route('admin.webmasters.index')
            ->with('success', 'Webmaster account removed.');
    }
}
