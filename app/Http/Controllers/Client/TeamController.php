<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Mail\TeamWelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class TeamController extends Controller
{
    public function index()
    {
        $members = User::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('client_id', Auth::user()->client_id)
            ->latest()
            ->get();

        return view('client.team.index', compact('members'));
    }

    public function create()
    {
        return view('client.team.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:admin,editor,viewer',
            'password' => 'required|min:8',
        ]);

        $data['client_id']          = Auth::user()->client_id;
        $data['password']           = Hash::make($data['password']);
        $data['email_verified_at']  = now();
        $data['status']             = 'active';

        $plainPassword = $request->password;
        $user = User::withoutGlobalScope(\App\Scopes\TenantScope::class)->create($data);

        try {
            Mail::to($user->email)->send(new TeamWelcomeMail($user, $plainPassword));
        } catch (\Throwable $e) {
            // Log but don't fail — user was created successfully
            \Illuminate\Support\Facades\Log::error('TeamWelcomeMail failed: ' . $e->getMessage());
        }

        return redirect()->route('dashboard.team.index')
            ->with('success', 'Team member added and welcome email sent.');
    }

    public function show(User $team)
    {
        return redirect()->route('dashboard.team.index');
    }

    public function edit(User $team)
    {
        return view('client.team.edit', compact('team'));
    }

    public function update(Request $request, User $team)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'role' => 'required|in:admin,editor,viewer',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $team->update($data);

        return redirect()->route('dashboard.team.index')
            ->with('success', 'Member updated.');
    }

    public function destroy(User $team)
    {
        if ($team->id === Auth::id()) {
            return back()->with('error', "Can't delete yourself.");
        }

        $team->delete();

        return redirect()->route('dashboard.team.index')
            ->with('success', 'Member removed.');
    }
}
