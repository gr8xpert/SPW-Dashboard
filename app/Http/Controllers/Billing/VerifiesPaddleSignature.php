<?php

namespace App\Http\Controllers\Billing;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait VerifiesPaddleSignature
{
    /**
     * Maximum allowed age of webhook timestamp (5 minutes) to prevent replay attacks.
     */
    protected int $maxTimestampAge = 300;

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
                $segments = explode('=', trim($part), 2);
                if (count($segments) !== 2) return [];
                return [$segments[0] => $segments[1]];
            });

        $ts = $parts->get('ts');
        $h1 = $parts->get('h1');

        if (!$ts || !$h1) return false;

        // SECURITY: Validate timestamp to prevent replay attacks
        $timestamp = (int) $ts;
        if (abs(time() - $timestamp) > $this->maxTimestampAge) {
            Log::warning('Paddle webhook timestamp too old or in future', [
                'timestamp' => $timestamp,
                'current_time' => time(),
                'diff_seconds' => abs(time() - $timestamp),
            ]);
            return false;
        }

        $payload = $ts . ':' . $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $h1);
    }
}
