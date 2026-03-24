<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ClientCustomFeatureGroup extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'slug',
        'parent_group_id',
        'parent_feed_feature_id',
        'parent_feed_feature_name',
        'sort_order',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($group) {
            if (empty($group->slug)) {
                $group->slug = static::generateUniqueSlug($group->client_id, $group->name);
            }
        });

        static::updating(function ($group) {
            if ($group->isDirty('name') && !$group->isDirty('slug')) {
                $group->slug = static::generateUniqueSlug($group->client_id, $group->name, $group->id);
            }
        });
    }

    public static function generateUniqueSlug(int $clientId, string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        $query = static::where('client_id', $clientId)->where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            $query = static::where('client_id', $clientId)->where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    // --- Relationships ---

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_group_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_group_id')->orderBy('sort_order');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(ClientFeatureMapping::class, 'custom_group_id')->orderBy('sort_order');
    }

    // --- Tree Building ---

    /**
     * Build tree structure for custom feature groups.
     * @param int $clientId The client ID
     * @param array $feedFeatures Optional array of feed features with translated names (keyed by ID)
     */
    public static function buildTree(int $clientId, array $feedFeatures = []): array
    {
        $groups = static::where('client_id', $clientId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['mappings', 'children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order')->with('mappings');
            }])
            ->get();

        return static::nestGroups($groups->where('parent_group_id', null)->values(), $feedFeatures);
    }

    /**
     * Recursively nest groups into tree structure.
     * @param $groups Collection of groups to nest
     * @param array $feedFeatures Feed features with translated names (keyed by ID)
     */
    protected static function nestGroups($groups, array $feedFeatures = []): array
    {
        $result = [];

        foreach ($groups as $group) {
            $item = [
                'id' => 'custom_feature_group_' . $group->id,
                'group_id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'type' => 'custom_group',
                'is_custom' => true,
                'children' => [],
            ];

            // Add mapped features - use translated name from feedFeatures if available
            foreach ($group->mappings as $mapping) {
                $featureId = (string) $mapping->feed_feature_id;
                $translatedName = $feedFeatures[$featureId] ?? $mapping->feed_feature_name;

                $item['children'][] = [
                    'id' => $mapping->feed_feature_id,
                    'name' => $translatedName,
                    'type' => 'feature',
                    'is_custom' => false,
                ];
            }

            // Add nested child groups
            if ($group->children->isNotEmpty()) {
                $nestedChildren = static::nestGroups($group->children, $feedFeatures);
                $item['children'] = array_merge($item['children'], $nestedChildren);
            }

            $result[] = $item;
        }

        return $result;
    }

    public function getAncestorIds(): array
    {
        $ids = [];
        $current = $this->parent;

        while ($current) {
            $ids[] = $current->id;
            $current = $current->parent;
        }

        return $ids;
    }

    public function isDescendantOf(int $groupId): bool
    {
        return in_array($groupId, $this->getAncestorIds());
    }
}
