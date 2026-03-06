<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientDomain;
use App\Models\LicenseKey;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WidgetSubscriptionService
{
    /**
     * Check subscription status for a domain. Used by widget proxy API.
     */
    public function checkSubscription(string $domain): array
    {
        $client = $this->findClientByDomain($domain);

        if (!$client) {
            return [
                'status'               => 'unknown',
                'allow_access'         => false,
                'grace_days_remaining' => 0,
                'admin_override'       => false,
                'plan'                 => null,
                'message'              => 'Domain not registered',
            ];
        }

        $allowAccess = $this->shouldAllowAccess($client);
        $graceDays = $client->getGraceDaysRemaining();

        $message = '';
        if ($client->subscription_status === 'grace') {
            $message = "Your subscription has expired. Widget will be deactivated in {$graceDays} days.";
        } elseif ($client->subscription_status === 'expired') {
            $message = 'Your subscription has expired. Please renew to restore widget access.';
        }

        return [
            'status'               => $client->subscription_status,
            'allow_access'         => $allowAccess,
            'grace_days_remaining' => $graceDays,
            'admin_override'       => (bool) $client->admin_override,
            'plan'                 => $client->plan ? [
                'name'     => $client->plan->name,
                'features' => $client->plan->features,
            ] : null,
            'message'              => $message,
        ];
    }

    /**
     * Core access control decision.
     * Admin override always wins, then internal flag, then billing status.
     */
    public function shouldAllowAccess(Client $client): bool
    {
        if ($client->admin_override) return true;
        if ($client->is_internal) return true;
        return in_array($client->subscription_status, ['active', 'grace', 'manual']);
    }

    /**
     * Get client configuration for widget proxy.
     */
    public function getClientConfig(string $domain): ?array
    {
        $client = $this->findClientByDomain($domain);
        if (!$client) return null;

        return [
            'api_key'              => $client->api_key,
            'api_url'              => $client->api_url,
            'enabled'              => $client->widget_enabled && $this->shouldAllowAccess($client),
            'features'             => $client->widget_features ?? [],
            'language'             => $client->default_language ?? 'en',
            'owner_email'          => $client->owner_email,
            'ai_search_enabled'    => $client->ai_search_enabled && ($client->plan?->ai_search_enabled ?? false),
            'openrouter_api_key'   => $client->ai_search_enabled ? $client->openrouter_api_key : null,
            'max_languages'        => $client->plan?->max_languages ?? 1,
            'enabledListingTypes'  => $this->getEnabledListingTypes($client),
        ];
    }

    /**
     * Extract enabled listing types from client's resales_settings.
     * Returns array of widget listing type keys (resale, development, short_rental, long_rental).
     */
    public function getEnabledListingTypes(Client $client): array
    {
        $resalesSettings = $client->resales_settings ?? [];
        $enabledTypes = [];

        // Map internal keys to widget keys
        $keyMap = [
            'resales' => 'resale',
            'developments' => 'development',
            'short_rentals' => 'short_rental',
            'long_rentals' => 'long_rental',
        ];

        foreach ($keyMap as $settingsKey => $widgetKey) {
            $settings = $resalesSettings[$settingsKey] ?? [];
            // Only include if explicitly enabled (default to false if not set)
            $isEnabled = $settings['enabled'] ?? false;
            if ($isEnabled) {
                $enabledTypes[] = $widgetKey;
            }
        }

        return $enabledTypes;
    }

    /**
     * Find client by primary domain or client_domains table.
     */
    public function findClientByDomain(string $domain): ?Client
    {
        // First check primary domain field on clients table
        $client = Client::where('domain', $domain)->with('plan')->first();
        if ($client) return $client;

        // Then check client_domains table (multi-domain support)
        $clientDomain = ClientDomain::where('domain', $domain)->first();
        if ($clientDomain) {
            return Client::with('plan')->find($clientDomain->client_id);
        }

        return null;
    }

    // --- Client management ---

    /**
     * Create a new client with widget features.
     */
    public function createClient(array $data, string $planSlug): ?Client
    {
        $plan = Plan::where('slug', $planSlug)->first();
        if (!$plan) return null;

        return DB::transaction(function () use ($data, $plan) {
            $client = Client::create(array_merge($data, [
                'plan_id'             => $plan->id,
                'subscription_status' => $data['billing_source'] === 'internal' ? 'internal' : 'active',
                'widget_enabled'      => true,
            ]));

            // Create primary domain record if domain provided
            if (!empty($data['domain'])) {
                ClientDomain::create([
                    'client_id'  => $client->id,
                    'domain'     => $data['domain'],
                    'is_primary' => true,
                    'verified'   => true,
                ]);
            }

            return $client;
        });
    }

    /**
     * Create an internal client (admin's own websites, never billed).
     */
    public function createInternalClient(array $data): Client
    {
        return $this->createClient(array_merge($data, [
            'billing_source'      => 'internal',
            'admin_override'      => true,
            'is_internal'         => true,
            'subscription_status' => 'internal',
        ]), $data['plan_slug'] ?? 'enterprise');
    }

    // --- License management ---

    public function validateLicense(string $key): array
    {
        $license = LicenseKey::where('license_key', $key)->with('plan')->first();

        if (!$license) {
            return ['valid' => false, 'error' => 'License key not found'];
        }

        if ($license->status === 'revoked') {
            return ['valid' => false, 'error' => 'License key has been revoked'];
        }

        if ($license->status === 'expired') {
            return ['valid' => false, 'error' => 'License key has expired'];
        }

        if ($license->status === 'activated') {
            return [
                'valid'    => true,
                'status'   => 'activated',
                'plan'     => $license->plan->name ?? null,
                'domain'   => $license->activated_domain,
            ];
        }

        return [
            'valid'  => true,
            'status' => 'unused',
            'plan'   => $license->plan->name ?? null,
        ];
    }

    public function activateLicense(string $key, string $domain, array $data = []): array
    {
        $license = LicenseKey::where('license_key', $key)->first();

        if (!$license || !$license->isUsable()) {
            return ['success' => false, 'error' => 'License key is not available for activation'];
        }

        return DB::transaction(function () use ($license, $domain, $data) {
            // Create or find client for this domain
            $client = $this->findClientByDomain($domain);

            if (!$client) {
                $client = $this->createClient(array_merge($data, [
                    'domain'         => $domain,
                    'billing_source' => 'paddle',
                ]), $license->plan->slug ?? 'starter');
            }

            $license->activate($domain, $client->id);

            return [
                'success'   => true,
                'client_id' => $client->id,
            ];
        });
    }

    public function generateLicenseKey(int $planId, ?string $notes = null): string
    {
        $license = LicenseKey::create([
            'plan_id' => $planId,
            'notes'   => $notes,
        ]);

        return $license->license_key;
    }

    // --- Subscription lifecycle ---

    public function startGracePeriod(int $clientId, int $days = 7): bool
    {
        $client = Client::find($clientId);
        if (!$client) return false;

        // Don't touch admin override or internal clients
        if ($client->admin_override || $client->is_internal) return false;

        $client->update([
            'subscription_status' => 'grace',
            'grace_ends_at'       => now()->addDays($days),
        ]);

        // TODO: Send "subscription expired, grace period" email
        Log::info("Grace period started for client {$clientId}, ends in {$days} days");

        return true;
    }

    public function expireSubscription(int $clientId): bool
    {
        $client = Client::find($clientId);
        if (!$client) return false;

        if ($client->admin_override || $client->is_internal) return false;

        $client->update([
            'subscription_status' => 'expired',
            'widget_enabled'      => false,
        ]);

        // TODO: Send "widget deactivated" email
        Log::info("Subscription expired for client {$clientId}");

        return true;
    }

    public function reactivateSubscription(int $clientId): bool
    {
        $client = Client::find($clientId);
        if (!$client) return false;

        $client->update([
            'subscription_status' => 'active',
            'widget_enabled'      => true,
            'grace_ends_at'       => null,
        ]);

        Log::info("Subscription reactivated for client {$clientId}");

        return true;
    }

    public function extendSubscription(int $clientId, string $period = '1 month'): bool
    {
        $client = Client::find($clientId);
        if (!$client) return false;

        $expiresAt = $client->subscription_expires_at ?? now();
        if ($expiresAt->isPast()) {
            $expiresAt = now();
        }

        $client->update([
            'subscription_status'    => 'active',
            'widget_enabled'         => true,
            'grace_ends_at'          => null,
            'subscription_expires_at' => $expiresAt->add(\DateInterval::createFromDateString($period)),
        ]);

        return true;
    }

    // --- Admin overrides ---

    public function setAdminOverride(int $clientId, bool $enabled): bool
    {
        $client = Client::find($clientId);
        if (!$client) return false;

        $client->update([
            'admin_override' => $enabled,
            'widget_enabled' => $enabled ? true : $client->widget_enabled,
        ]);

        return true;
    }

    public function setInternalFlag(int $clientId, bool $enabled): bool
    {
        $client = Client::find($clientId);
        if (!$client) return false;

        $client->update([
            'is_internal'    => $enabled,
            'billing_source' => $enabled ? 'internal' : $client->billing_source,
        ]);

        return true;
    }

    public function manualActivate(int $clientId, ?string $expiresAt = null): bool
    {
        $client = Client::find($clientId);
        if (!$client) return false;

        $updates = [
            'subscription_status' => 'manual',
            'billing_source'      => 'manual',
            'widget_enabled'      => true,
            'grace_ends_at'       => null,
        ];

        if ($expiresAt) {
            $updates['subscription_expires_at'] = $expiresAt;
        }

        $client->update($updates);

        return true;
    }
}
