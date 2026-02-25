<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secret = config('services.stripe.webhook.secret');
        $payload = $request->getContent();
        $sig = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature invalid: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        match ($event->type) {
            'invoice.paid'                     => $this->handleInvoicePaid($event->data->object),
            'invoice.payment_failed'           => $this->handlePaymentFailed($event->data->object),
            'customer.subscription.deleted'    => $this->handleSubscriptionDeleted($event->data->object),
            'customer.subscription.updated'    => $this->handleSubscriptionUpdated($event->data->object),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    protected function handleInvoicePaid(object $invoice): void
    {
        $client = Client::where('stripe_customer_id', $invoice->customer)->first();
        if ($client && $client->status !== 'active') {
            $client->update(['status' => 'active']);
        }
    }

    protected function handlePaymentFailed(object $invoice): void
    {
        Log::warning('Payment failed for customer: ' . $invoice->customer);
        // Send warning email — implement notification here
    }

    protected function handleSubscriptionDeleted(object $subscription): void
    {
        $client = Client::where('stripe_subscription_id', $subscription->id)->first();
        if ($client) {
            $freePlan = Plan::where('slug', 'free')->first();
            $client->update([
                'plan_id' => $freePlan?->id,
                'stripe_subscription_id' => null,
            ]);
        }
    }

    protected function handleSubscriptionUpdated(object $subscription): void
    {
        // Sync plan when subscription changes
        Log::info('Subscription updated: ' . $subscription->id);
    }
}
