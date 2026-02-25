<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LicenseKey extends Model
{
    protected $fillable = [
        'license_key', 'client_id', 'plan_id', 'status',
        'activated_at', 'activated_domain', 'notes',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($key) {
            if (empty($key->license_key)) {
                $key->license_key = strtoupper(Str::random(8) . '-' . Str::random(8) . '-' . Str::random(8) . '-' . Str::random(8));
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function installerSessions()
    {
        return $this->hasMany(InstallerSession::class, 'license_key_id');
    }

    public function isUsable(): bool
    {
        return $this->status === 'unused';
    }

    public function isActivated(): bool
    {
        return $this->status === 'activated';
    }

    public function activate(string $domain, ?int $clientId = null): void
    {
        $this->update([
            'status'           => 'activated',
            'activated_at'     => now(),
            'activated_domain' => $domain,
            'client_id'        => $clientId ?? $this->client_id,
        ]);
    }

    public function revoke(): void
    {
        $this->update(['status' => 'revoked']);
    }
}
