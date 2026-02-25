<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InstallerSession extends Model
{
    protected $fillable = [
        'session_token', 'license_key_id', 'domain', 'platform',
        'languages', 'page_slugs', 'status', 'generated_files',
    ];

    protected $casts = [
        'languages'       => 'array',
        'page_slugs'      => 'array',
        'generated_files' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($session) {
            if (empty($session->session_token)) {
                $session->session_token = Str::random(64);
            }
        });
    }

    public function licenseKey()
    {
        return $this->belongsTo(LicenseKey::class, 'license_key_id');
    }

    public function markCompleted(array $generatedFiles = []): void
    {
        $this->update([
            'status'          => 'completed',
            'generated_files' => $generatedFiles,
        ]);
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
