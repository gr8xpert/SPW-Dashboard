<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ImpersonationService
{
    /**
     * Start impersonating a client's admin user.
     */
    public function impersonate(int $clientId): bool
    {
        $adminUser = User::where('client_id', $clientId)
            ->where('role', 'admin')
            ->first();

        if (!$adminUser) return false;

        // Store original admin ID
        session(['impersonated_by' => auth()->id()]);
        session(['original_user_id' => auth()->id()]);

        AuditLog::log('impersonation.started', 'client', $clientId, [
            'impersonator' => auth()->user()->name,
            'target_user'  => $adminUser->name,
        ]);

        Auth::login($adminUser);

        return true;
    }

    /**
     * Stop impersonating and return to original admin.
     */
    public function stopImpersonating(): bool
    {
        $originalUserId = session('original_user_id');
        if (!$originalUserId) return false;

        $originalUser = User::find($originalUserId);
        if (!$originalUser) return false;

        AuditLog::log('impersonation.stopped', 'user', $originalUserId, [
            'was_impersonating' => auth()->user()->name,
        ]);

        session()->forget(['impersonated_by', 'original_user_id']);

        Auth::login($originalUser);

        return true;
    }

    /**
     * Check if current session is impersonated.
     */
    public static function isImpersonating(): bool
    {
        return session()->has('impersonated_by');
    }
}
