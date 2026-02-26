<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\CreditTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaddlePlatformWebhookController extends Controller
{
    use VerifiesPaddleSignature;

    public function handle(Request $request)
    {
        if (!$this->verifyPaddleSignature($request, 'smartmailer.paddle_platform.webhook_secret')) {
            Log::error('Paddle Platform webhook signature verification failed');
            AuditLog::create([
                'action'     => 'paddle.platform.webhook.rejected',
                'details'    => ['reason' => 'Invalid signature'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $eventType = $request->input('event_type');
        $data = $request->input('data', []);

        Log::info("Paddle Platform webhook received: {$eventType}", ['data' => $data]);

        AuditLog::create([
            'action'     => "paddle.platform.{$eventType}",
            'details'    => $data,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        match ($eventType) {
            'transaction.completed' => $this->handleTransactionCompleted($data),
            default => Log::info("Unhandled Paddle Platform event: {$eventType}"),
        };

        return response()->json(['received' => true]);
    }

    protected function handleTransactionCompleted(array $data): void
    {
        $customData = $data['custom_data'] ?? [];
        $type = $customData['type'] ?? null;

        match ($type) {
            'credit_purchase' => $this->handleCreditPurchase($data, $customData),
            default => Log::info('Platform transaction completed (unhandled type)', [
                'type' => $type,
                'data' => $data,
            ]),
        };
    }

    protected function handleCreditPurchase(array $data, array $customData): void
    {
        $clientId = $customData['client_id'] ?? null;
        $hours = (float) ($customData['hours'] ?? 0);
        $rate = (float) ($customData['rate'] ?? 0);

        if (!$clientId || !$hours) {
            Log::warning('Credit purchase webhook missing client_id or hours', ['data' => $data]);
            return;
        }

        DB::transaction(function () use ($clientId, $hours, $rate, $data) {
            $client = Client::lockForUpdate()->find($clientId);
            if (!$client) return;

            // Store Paddle Platform customer ID if not already set
            $customerId = $data['customer_id'] ?? null;
            if ($customerId && !$client->paddle_platform_customer_id) {
                $client->paddle_platform_customer_id = $customerId;
            }

            $client->increment('credit_balance', $hours);
            $client->save();

            CreditTransaction::create([
                'client_id'     => $clientId,
                'user_id'       => $client->users()->where('role', 'admin')->first()?->id ?? 0,
                'type'          => 'purchase',
                'hours'         => $hours,
                'rate'          => $rate,
                'description'   => "Purchased {$hours} credit hours via Paddle",
                'balance_after' => $client->fresh()->credit_balance,
            ]);
        });

        Log::info("Platform credit purchase completed: {$hours}h for client {$clientId}");
    }
}
