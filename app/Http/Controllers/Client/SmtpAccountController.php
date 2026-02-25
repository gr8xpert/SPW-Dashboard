<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SmtpAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SmtpAccountController extends Controller
{
    public function index()
    {
        $accounts = SmtpAccount::latest()->paginate(20);

        return view('client.smtp-accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('client.smtp-accounts.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'provider'    => 'required|in:ses,sendgrid,mailgun,postmark,smtp,platform',
            'host'        => 'required|string|max:200',
            'port'        => 'required|integer|min:1|max:65535',
            'username'    => 'required|string|max:200',
            'password'    => 'required|string',
            'encryption'  => 'required|in:tls,ssl,none',
            'from_email'  => 'nullable|email',
            'from_name'   => 'nullable|string|max:200',
            'daily_limit' => 'nullable|integer|min:1',
        ]);

        $data['password_encrypted'] = Crypt::encryptString($data['password']);
        unset($data['password']);

        SmtpAccount::create($data);

        return redirect()->route('dashboard.smtp-accounts.index')
            ->with('success', 'SMTP account added.');
    }

    public function show(SmtpAccount $smtpAccount)
    {
        return view('client.smtp-accounts.show', compact('smtpAccount'));
    }

    public function edit(SmtpAccount $smtpAccount)
    {
        return view('client.smtp-accounts.edit', compact('smtpAccount'));
    }

    public function update(Request $request, SmtpAccount $smtpAccount)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'from_email'  => 'nullable|email',
            'from_name'   => 'nullable|string|max:200',
            'daily_limit' => 'nullable|integer|min:1',
        ]);

        if ($request->filled('password')) {
            $data['password_encrypted'] = Crypt::encryptString($request->password);
        }

        $smtpAccount->update($data);

        return redirect()->route('dashboard.smtp-accounts.index')
            ->with('success', 'SMTP account updated.');
    }

    public function destroy(SmtpAccount $smtpAccount)
    {
        $smtpAccount->delete();

        return redirect()->route('dashboard.smtp-accounts.index')
            ->with('success', 'SMTP account deleted.');
    }

    public function test(Request $request, SmtpAccount $smtpAccount)
    {
        // TODO: Send test via SMTP
        return back()->with('success', 'Test email queued (feature coming soon).');
    }

    public function setDefault(Request $request, SmtpAccount $smtpAccount)
    {
        SmtpAccount::where('client_id', $smtpAccount->client_id)
            ->update(['is_default' => false]);

        $smtpAccount->update(['is_default' => true]);

        return back()->with('success', 'Set as default SMTP account.');
    }
}
