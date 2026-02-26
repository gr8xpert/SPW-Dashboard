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
    protected string $account;

    public function __construct(string $account = 'widget')
    {
        $this->account = $account;
        $this->apiKey = config("smartmailer.paddle_{$account}.api_key", '');
        $this->vendorId = config("smartmailer.paddle_{$account}.vendor_id", '');
        $this->sandbox = (bool) config("smartmailer.paddle_{$account}.sandbox", true);
        $this->baseUrl = $this->sandbox
            ? 'https://sandbox-api.paddle.com'
            : 'https://api.paddle.com';
    }

    /**
     * Factory method — PaddleBillingService::for('widget') or ::for('platform').
     */
    public static function for(string $account): static
    {
        return new static($account);
    }

    /**
     * Get the vendor ID (for frontend Paddle.js initialization).
     */
    public function getVendorId(): string
    {
        return $this->vendorId;
    }

    /**
     * Whether this account is in sandbox mode (for frontend environment toggle).
     */
    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * Get checkout overlay configuration for frontend.
     */
    public function getCheckoutConfig(string $priceId, string $customerEmail, array $customData = []): array
    {
        return [
            'vendor_id'   => $this->vendorId,
            'sandbox'     => $this->sandbox,
            'items'       => [['priceId' => $priceId, 'quantity' => 1]],
            'customer'    => ['email' => $customerEmail],
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
                Log::info("Paddle [{$this->account}] subscription {$subscriptionId} cancelled");
                return true;
            }

            Log::error("Failed to cancel Paddle [{$this->account}] subscription {$subscriptionId}", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Paddle [{$this->account}] API error cancelling subscription: {$e->getMessage()}");
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
            Log::error("Paddle [{$this->account}] API error getting subscription: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Create a one-time transaction checkout config for credit purchase.
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
