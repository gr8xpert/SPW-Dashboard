<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'max_contacts', 'max_emails_per_month',
        'max_templates', 'max_users', 'max_image_storage_mb',
        'features', 'price_monthly', 'price_yearly', 'is_active', 'sort_order',
        // Widget fields
        'max_languages', 'ai_search_enabled', 'widget_included', 'mailer_included',
    ];

    protected $casts = [
        'features'          => 'array',
        'is_active'         => 'boolean',
        'ai_search_enabled' => 'boolean',
        'widget_included'   => 'boolean',
        'mailer_included'   => 'boolean',
        'price_monthly'     => 'decimal:2',
        'price_yearly'      => 'decimal:2',
    ];

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function licenseKeys()
    {
        return $this->hasMany(LicenseKey::class);
    }

    public function getFeature(string $key, $default = false)
    {
        return $this->features[$key] ?? $default;
    }
}
