<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Services\WidgetSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaddleWidgetWebhookController extends Controller
{
    use VerifiesPaddleSignature;

    public function __construct(
        protected WidgetSubscriptionService $subscriptionService
    ) {}

    public function handle(Request $request)
    {
        if (!$this->verifyPaddleSignature($request, 'smartmailer.paddle_widget.webhook_secret')) {
            Log::error('Paddle Widget webhook signature verification failed');
            AuditLog::create([
                'action'     => 'paddle.widget.webhook.rejected',
                'details'    => ['reason' => 'Invalid signature'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $eventType = $request->input('event_type');
        $data = $request->input('data', []);

        Log::info("Paddle Widget webhook received: {$eventType}", ['data' => $data]);

        AuditLog::create([
            'action'     => "paddle.widget.{$eventType}",
            'details'    => $data,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        match ($eventType) {
            'subscription.created'           => $this->handleSubscriptionCreated($data),
            'subscription.updated'           => $this->handleSubscriptionUpdated($data),
            'subscription.canceled'          => $this->handleSubscriptionCanceled($data),
            'subscription.paused'            => $this->handleSubscriptionCanceled($data),
            'subscription.payment_succeeded' => $this->handlePaymentSucceeded($data),
            'subscription.payment_failed'    => $this->handlePaymentFailed($data),
            default => Log::info("Unhandled Paddle Widget event: {$eventType}"),
        };

        return response()->json(['received' => true]);
    }

    protected function handleSubscriptionCreated(array $data): void
    {
        $customerId = $data['customer_id'] ?? null;
        $subscriptionId = $data['id'] ?? null;

        $client = Client::where('paddle_customer_id', $customerId)->first();
        if (!$client) {
            Log::warning("Paddle Widget subscription.created: No client found for customer {$customerId}");
            return;
        }

        $client->update([
            'paddle_subscription_id' => $subscriptionId,
            'subscription_status'    => 'active',
            'billing_source'         => 'paddle',
            'widget_enabled'         => true,
            'grace_ends_at'          => null,
        ]);

        Log::info("Widget subscription created for client {$client->id}");
    }

    protected function handleSubscriptionUpdated(array $data): void
    {
        $subscriptionId = $data['id'] ?? null;
        $client = Client::where('paddle_subscription_id', $subscriptionId)->first();

        if (!$client) {
            Log::warning("Paddle Widget subscription.updated: No client for subscription {$subscriptionId}");
            return;
        }

        Log::info("Widget subscription updated for client {$client->id}", ['data' => $data]);
    }

    protected function handleSubscriptionCanceled(array $data): void
    {
        $subscriptionId = $data['id'] ?? null;
        $client = Client::where('paddle_subscription_id', $subscriptionId)->first();

        if (!$client) {
            Log::warning("Paddle Widget subscription.canceled: No client for subscription {$subscriptionId}");
            return;
        }

        if ($client->admin_override || $client->is_internal) {
            Log::info("Widget subscription canceled for client {$client->id} but admin_override/internal — ignoring");
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
            Log::warning("Paddle Widget payment_succeeded: No client for subscription {$subscriptionId}");
            return;
        }

        $this->subscriptionService->reactivateSubscription($client->id);
        Log::info("Widget subscription reactivated after payment for client {$client->id}");
    }

    protected function handlePaymentFailed(array $data): void
    {
        $subscriptionId = $data['subscription_id'] ?? $data['id'] ?? null;
        $client = Client::where('paddle_subscription_id', $subscriptionId)->first();

        if (!$client) {
            Log::warning("Paddle Widget payment_failed: No client for subscription {$subscriptionId}");
            return;
        }

        if ($client->admin_override || $client->is_internal) {
            return;
        }

        if ($client->subscription_status !== 'grace') {
            $this->subscriptionService->startGracePeriod($client->id);
        }

        Log::warning("Widget payment failed for client {$client->id}");
    }
}
