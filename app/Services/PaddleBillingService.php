<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaddleBillingService
{
    protected string $apiKey;
    protected string $vendorId;
    protected bool $sandbox;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('smartmailer.paddle.api_key', '');
        $this->vendorId = config('smartmailer.paddle.vendor_id', '');
        $this->sandbox = config('smartmailer.paddle.sandbox', true);
        $this->baseUrl = $this->sandbox
            ? 'https://sandbox-api.paddle.com'
            : 'https://api.paddle.com';
    }

    /**
     * Generate a Paddle checkout overlay URL for a subscription.
     */
    public function getCheckoutUrl(string $priceId, array $customData = []): ?string
    {
        // Paddle checkout is handled via JS overlay on the frontend.
        // This method returns the data needed for the Paddle.js initialization.
        return null; // Paddle.js handles this client-side
    }

    /**
     * Get checkout overlay configuration for frontend.
     */
    public function getCheckoutConfig(string $priceId, string $customerEmail, array $customData = []): array
    {
        return [
            'vendor_id' => $this->vendorId,
            'sandbox'   => $this->sandbox,
            'items'     => [['priceId' => $priceId, 'quantity' => 1]],
            'customer'  => ['email' => $customerEmail],
            'custom_data' => $customData,
        ];
    }

    /**
     * Cancel a subscription via Paddle API.
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->post("{$this->baseUrl}/subscriptions/{$subscriptionId}/cancel", [
                    'effective_from' => 'next_billing_period',
                ]);

            if ($response->successful()) {
                Log::info("Paddle subscription {$subscriptionId} cancelled");
                return true;
            }

            Log::error("Failed to cancel Paddle subscription {$subscriptionId}", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Paddle API error cancelling subscription: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get subscription details from Paddle.
     */
    public function getSubscription(string $subscriptionId): ?array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->get("{$this->baseUrl}/subscriptions/{$subscriptionId}");

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Paddle API error getting subscription: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Create a one-time transaction for credit purchase.
     */
    public function createCreditPurchaseConfig(
        int $clientId,
        float $hours,
        float $rate,
        string $priceId,
        string $customerEmail
    ): array {
        return $this->getCheckoutConfig($priceId, $customerEmail, [
            'type'      => 'credit_purchase',
            'client_id' => $clientId,
            'hours'     => $hours,
            'rate'      => $rate,
        ]);
    }
}
