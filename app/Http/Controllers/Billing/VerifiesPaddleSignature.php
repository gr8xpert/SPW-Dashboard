<?php

namespace App\Http\Controllers\Billing;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait VerifiesPaddleSignature
{
    protected function verifyPaddleSignature(Request $request, string $configKey): bool
    {
        $secret = config($configKey);

        if (empty($secret)) {
            Log::warning("Paddle webhook secret not configured ({$configKey}) — allowing in development");
            return app()->environment('local', 'testing');
        }

        $signature = $request->header('Paddle-Signature');
        if (!$signature) return false;

        // Parse Paddle signature: ts=...; h1=...
        $parts = collect(explode(';', $signature))
            ->mapWithKeys(function ($part) {
                [$key, $value] = explode('=', trim($part), 2);
                return [$key => $value];
            });

        $ts = $parts->get('ts');
        $h1 = $parts->get('h1');

        if (!$ts || !$h1) return false;

        $payload = $ts . ':' . $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $h1);
    }
}
