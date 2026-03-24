<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Client extends Model
{
    protected $fillable = [
        'company_name', 'subdomain', 'custom_domain', 'plan_id', 'billing_cycle',
        'status', 'trial_ends_at', 'stripe_customer_id', 'stripe_subscription_id',
        'api_key', 'api_secret', 'timezone',
        // Widget fields
        'domain', 'api_url', 'site_name', 'widget_features', 'widget_config', 'ai_search_enabled',
        'openrouter_api_key', 'default_language', 'owner_email',
        'widget_enabled', 'admin_override', 'is_internal', 'billing_source',
        // Resales Online API credentials
        'resales_client_id', 'resales_api_key', 'resales_filter_id', 'resales_agency_code',
        'resales_settings', 'enabled_languages', 'custom_location_grouping_enabled',
        'custom_property_type_grouping_enabled', 'custom_feature_grouping_enabled',
        'location_parent_type', 'location_child_type',
        // Paddle billing
        'paddle_subscription_id', 'paddle_customer_id', 'paddle_platform_customer_id',
        'subscription_status', 'grace_ends_at', 'subscription_expires_at',
        // Credit hours
        'credit_balance', 'credit_rate',
    ];

    protected $casts = [
        'trial_ends_at'          => 'datetime',
        'grace_ends_at'          => 'datetime',
        'subscription_expires_at' => 'datetime',
        'widget_features'        => 'array',
        'widget_config'          => 'array',
        'ai_search_enabled'      => 'boolean',
        'widget_enabled'         => 'boolean',
        'admin_override'         => 'boolean',
        'is_internal'            => 'boolean',
        'credit_balance'         => 'decimal:2',
        'credit_rate'            => 'decimal:2',
        'openrouter_api_key'     => 'encrypted',
        'resales_api_key'        => 'encrypted',
        'resales_settings'       => 'array',
        'enabled_languages'      => 'array',
        'custom_location_grouping_enabled' => 'boolean',
        'custom_property_type_grouping_enabled' => 'boolean',
        'custom_feature_grouping_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($client) {
            if (empty($client->api_key)) {
                $client->api_key = Str::random(64);
            }
        });
    }

    // --- Relationships ---

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function usage()
    {
        return $this->hasMany(ClientUsage::class);
    }

    public function brandKit()
    {
        return $this->hasOne(BrandKit::class);
    }

    public function licenseKeys()
    {
        return $this->hasMany(LicenseKey::class);
    }

    public function widgetAnalytics()
    {
        return $this->hasMany(WidgetAnalytic::class);
    }

    public function domains()
    {
        return $this->hasMany(ClientDomain::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function labelOverrides()
    {
        return $this->hasMany(ClientLabelOverride::class);
    }

    public function displayPreferences()
    {
        return $this->hasMany(ClientDisplayPreference::class);
    }

    public function customLocationGroups()
    {
        return $this->hasMany(ClientCustomLocationGroup::class);
    }

    public function locationMappings()
    {
        return $this->hasMany(ClientLocationMapping::class);
    }

    // --- Status checks ---

    public function isActive(): bool
    {
        return in_array($this->status, ['trial', 'active']);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isInGracePeriod(): bool
    {
        return $this->subscription_status === 'grace'
            && $this->grace_ends_at
            && $this->grace_ends_at->isFuture();
    }

    public function isSubscriptionExpired(): bool
    {
        return $this->subscription_status === 'expired';
    }

    public function shouldAllowWidgetAccess(): bool
    {
        if ($this->admin_override) return true;
        if ($this->is_internal) return true;
        return in_array($this->subscription_status, ['active', 'grace', 'manual']);
    }

    public function getGraceDaysRemaining(): int
    {
        if (!$this->grace_ends_at || $this->subscription_status !== 'grace') {
            return 0;
        }
        return max(0, (int) now()->diffInDays($this->grace_ends_at, false));
    }

    public function getCurrentUsage(): ?ClientUsage
    {
        return $this->usage()->where('month', now()->format('Y-m'))->first();
    }

    public function hasSufficientCredits(float $hours): bool
    {
        return $this->credit_balance >= $hours;
    }
}
