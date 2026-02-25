<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\CreditTransaction;
use App\Services\WidgetSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaddleWebhookController extends Controller
{
    public function __construct(
        protected WidgetSubscriptionService $subscriptionService
    ) {}

    public function handle(Request $request)
    {
        // Verify Paddle webhook signature
        if (!$this->verifySignature($request)) {
            Log::error('Paddle webhook signature verification failed');
            AuditLog::create([
                'action'     => 'paddle.webhook.rejected',
                'details'    => ['reason' => 'Invalid signature'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $eventType = $request->input('event_type');
        $data = $request->input('data', []);

        Log::info("Paddle webhook received: {$eventType}", ['data' => $data]);

        AuditLog::create([
            'action'     => "paddle.webhook.{$eventType}",
            'details'    => $data,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        match ($eventType) {
            'subscription.created'          => $this->handleSubscriptionCreated($data),
            'subscription.updated'          => $this->handleSubscriptionUpdated($data),
            'subscription.canceled'         => $this->handleSubscriptionCanceled($data),
            'subscription.paused'           => $this->handleSubscriptionCanceled($data),
            'transaction.completed'         => $this->handleTransactionCompleted($data),
            'subscription.payment_succeeded' => $this->handlePaymentSucceeded($data),
            'subscription.payment_failed'   => $this->handlePaymentFailed($data),
            default => Log::info("Unhandled Paddle event: {$eventType}"),
        };

        return response()->json(['received' => true]);
    }

    protected function handleSubscriptionCreated(array $data): void
    {
        $customerId = $data['customer_id'] ?? null;
        $subscriptionId = $data['id'] ?? null;

        $client = Client::where('paddle_customer_id', $customerId)->first();
        if (!$client) {
            Log::warning("Paddle subscription.created: No client found for customer {$customerId}");
            return;
        }

        $client->update([
            'paddle_subscription_id' => $subscriptionId,
            'subscription_status'    => 'active',
            'billing_source'         => 'paddle',
            'widget_enabled'         => true,
            'grace_ends_at'          => null,
        ]);

        Log::info("Subscription created for client {$client->id}");
    }

    protected function handleSubscriptionUpdated(array $data): void
    {
        $subscriptionId = $data['id'] ?? null;
        $client = Client::where('paddle_subscription_id', $subscriptionId)->first();

        if (!$client) {
            Log::warning("Paddle subscription.updated: No client for subscription {$subscriptionId}");
            return;
        }

        // If Paddle sends new plan/price info, handle plan tier changes here
        Log::info("Subscription updated for client {$client->id}", ['data' => $data]);
    }

    protected function handleSubscriptionCanceled(array $data): void
    {
        $subscriptionId = $data['id'] ?? null;
        $client = Client::where('paddle_subscription_id', $subscriptionId)->first();

        if (!$client) {
            Log::warning("Paddle subscription.canceled: No client for subscription {$subscriptionId}");
            return;
        }

        // Skip if admin override or internal
        if ($client->admin_override || $client->is_internal) {
            Log::info("Subscription canceled for client {$client->id} but admin_override/internal — ignoring");
            return;
        }

        $this->subscriptionService->startGracePeriod($client->id);
        Log::info("Grace period started for client {$client->id}");
    }

    protected function handlePaymentSucceeded(array $data): void
    {
        $subscriptionId = $data['subscription_id'] ?? $data['id'] ?? null;
        $client = Client::where('paddle_subscription_id', $subscriptionId)->first();

        if (!$client) {
            Log::warning("Paddle payment_succeeded: No client for subscription {$subscriptionId}");
            return;
        }

        $this->subscriptionService->reactivateSubscription($client->id);
        Log::info("Subscription reactivated after payment for client {$client->id}");
    }

    protected function handlePaymentFailed(array $data): void
    {
        $subscriptionId = $data['subscription_id'] ?? $data['id'] ?? null;
        $client = Client::where('paddle_subscription_id', $subscriptionId)->first();

        if (!$client) {
            Log::warning("Paddle payment_failed: No client for subscription {$subscriptionId}");
            return;
        }

        // Skip if admin override or internal
        if ($client->admin_override || $client->is_internal) {
            return;
        }

        // Start grace if not already in grace
        if ($client->subscription_status !== 'grace') {
            $this->subscriptionService->startGracePeriod($client->id);
        }

        // TODO: Send warning email to client
        Log::warning("Payment failed for client {$client->id}");
    }

    protected function handleTransactionCompleted(array $data): void
    {
        // Handle one-time purchases (e.g., credit hour packs)
        $customData = $data['custom_data'] ?? [];

        if (($customData['type'] ?? null) === 'credit_purchase') {
            $this->handleCreditPurchase($data, $customData);
            return;
        }

        Log::info('Transaction completed (non-credit)', ['data' => $data]);
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

            $client->increment('credit_balance', $hours);

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

        Log::info("Credit purchase completed: {$hours}h for client {$clientId}");
    }

    protected function verifySignature(Request $request): bool
    {
        $secret = config('smartmailer.paddle.webhook_secret');

        if (empty($secret)) {
            Log::warning('Paddle webhook secret not configured — allowing in development');
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
