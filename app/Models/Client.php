<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Client extends Model
{
    protected $fillable = [
        'company_name', 'subdomain', 'custom_domain', 'plan_id',
        'status', 'trial_ends_at', 'stripe_customer_id', 'stripe_subscription_id',
        'api_key', 'api_secret', 'timezone',
        // Widget fields
        'domain', 'api_url', 'widget_features', 'ai_search_enabled',
        'openrouter_api_key', 'default_language', 'owner_email',
        'widget_enabled', 'admin_override', 'is_internal', 'billing_source',
        // Paddle billing
        'paddle_subscription_id', 'paddle_customer_id',
        'subscription_status', 'grace_ends_at', 'subscription_expires_at',
        // Credit hours
        'credit_balance', 'credit_rate',
    ];

    protected $casts = [
        'trial_ends_at'          => 'datetime',
        'grace_ends_at'          => 'datetime',
        'subscription_expires_at' => 'datetime',
        'widget_features'        => 'array',
        'ai_search_enabled'      => 'boolean',
        'widget_enabled'         => 'boolean',
        'admin_override'         => 'boolean',
        'is_internal'            => 'boolean',
        'credit_balance'         => 'decimal:2',
        'credit_rate'            => 'decimal:2',
        'openrouter_api_key'     => 'encrypted',
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
