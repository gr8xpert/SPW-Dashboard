<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return $this->redirectAfterLogin(Auth::user());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $key = 'login.' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, config('smartmailer.security.login_max_attempts', 5))) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $user = User::where('email', $request->email)->first();

        // Check if user is locked
        if ($user && $user->isLocked()) {
            $minutes = now()->diffInMinutes($user->locked_until);
            throw ValidationException::withMessages([
                'email' => "Account is temporarily locked. Try again in {$minutes} minutes.",
            ]);
        }

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($key, config('smartmailer.security.login_lockout_minutes', 15) * 60);

            // Increment failed attempts
            if ($user) {
                $attempts = $user->failed_login_attempts + 1;
                $data = ['failed_login_attempts' => $attempts];

                if ($attempts >= config('smartmailer.security.login_max_attempts', 5)) {
                    $data['locked_until'] = now()->addMinutes(
                        config('smartmailer.security.login_lockout_minutes', 15)
                    );
                }

                $user->update($data);
            }

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($key);

        $user = Auth::user();
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'last_login_at'         => now(),
            'last_login_ip'         => $request->ip(),
        ]);

        // Check 2FA
        if ($user->two_factor_enabled) {
            Auth::logout();
            $request->session()->put('2fa_user_id', $user->id);
            return redirect()->route('2fa.show');
        }

        $request->session()->regenerate();

        return $this->redirectAfterLogin($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    protected function redirectAfterLogin(User $user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('dashboard.home');
    }
}
