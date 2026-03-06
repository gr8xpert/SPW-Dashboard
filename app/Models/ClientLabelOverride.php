<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientLabelOverride extends Model
{
    protected $fillable = [
        'client_id',
        'language',
        'label_key',
        'label_value',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get all label overrides for a client and language as key-value array.
     */
    public static function getOverridesForClient(int $clientId, string $language): array
    {
        return static::where('client_id', $clientId)
            ->where('language', $language)
            ->pluck('label_value', 'label_key')
            ->toArray();
    }

    /**
     * Upsert a label override for a client.
     */
    public static function setOverride(int $clientId, string $language, string $key, string $value): static
    {
        return static::updateOrCreate(
            ['client_id' => $clientId, 'language' => $language, 'label_key' => $key],
            ['label_value' => $value]
        );
    }

    /**
     * Remove a label override (revert to default).
     */
    public static function removeOverride(int $clientId, string $language, string $key): bool
    {
        return static::where('client_id', $clientId)
            ->where('language', $language)
            ->where('label_key', $key)
            ->delete() > 0;
    }
}
