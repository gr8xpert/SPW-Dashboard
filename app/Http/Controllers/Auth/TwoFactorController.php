<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function show(Request $request)
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }
        return view('auth.2fa');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $userId = $request->session()->get('2fa_user_id');
        $user = User::findOrFail($userId);

        $valid = $this->google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $request->code
        );

        if (!$valid) {
            return back()->withErrors(['code' => 'Invalid 2FA code. Please try again.']);
        }

        $request->session()->forget('2fa_user_id');
        Auth::login($user);
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return $user->isSuperAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('dashboard.home');
    }

    public function setup(Request $request)
    {
        $user = $request->user();
        $secret = $this->google2fa->generateSecretKey();
        $qrCode = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $request->session()->put('2fa_setup_secret', $secret);

        return view('auth.2fa-setup', compact('qrCode', 'secret'));
    }

    public function enable(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $secret = $request->session()->get('2fa_setup_secret');
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Invalid code. Please scan the QR code again.']);
        }

        $request->user()->update([
            'two_factor_secret'  => encrypt($secret),
            'two_factor_enabled' => true,
        ]);

        $request->session()->forget('2fa_setup_secret');

        return redirect()->route('dashboard.home')->with('success', '2FA has been enabled successfully.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        $request->user()->update([
            'two_factor_secret'  => null,
            'two_factor_enabled' => false,
        ]);

        return redirect()->route('dashboard.settings.index')->with('success', '2FA has been disabled.');
    }
}
