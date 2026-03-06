<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientDisplayPreference extends Model
{
    protected $fillable = [
        'client_id',
        'item_type',
        'item_id',
        'item_name',
        'visible',
        'sort_order',
        'custom_name',
    ];

    protected $casts = [
        'visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get visible items of a specific type for a client, ordered by sort_order.
     */
    public static function getVisibleItems(int $clientId, string $itemType): array
    {
        return static::where('client_id', $clientId)
            ->where('item_type', $itemType)
            ->where('visible', true)
            ->orderBy('sort_order')
            ->pluck('item_id')
            ->toArray();
    }

    /**
     * Get hidden items of a specific type for a client.
     */
    public static function getHiddenItems(int $clientId, string $itemType): array
    {
        return static::where('client_id', $clientId)
            ->where('item_type', $itemType)
            ->where('visible', false)
            ->pluck('item_id')
            ->toArray();
    }

    /**
     * Get all preferences for a client and item type as array.
     */
    public static function getPreferencesForClient(int $clientId, string $itemType): array
    {
        return static::where('client_id', $clientId)
            ->where('item_type', $itemType)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('item_id')
            ->toArray();
    }

    /**
     * Bulk update preferences for a client.
     * $items = [['item_id' => '123', 'visible' => true, 'sort_order' => 1], ...]
     */
    public static function bulkUpdatePreferences(int $clientId, string $itemType, array $items): void
    {
        foreach ($items as $item) {
            static::updateOrCreate(
                [
                    'client_id' => $clientId,
                    'item_type' => $itemType,
                    'item_id' => $item['item_id'],
                ],
                [
                    'item_name' => $item['item_name'] ?? null,
                    'visible' => $item['visible'] ?? true,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'custom_name' => $item['custom_name'] ?? null,
                ]
            );
        }
    }
}
