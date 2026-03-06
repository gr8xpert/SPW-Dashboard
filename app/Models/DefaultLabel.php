<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultLabel extends Model
{
    protected $fillable = [
        'language',
        'label_key',
        'label_value',
    ];

    /**
     * Get all labels for a specific language as key-value array.
     */
    public static function getLabelsForLanguage(string $language): array
    {
        return static::where('language', $language)
            ->pluck('label_value', 'label_key')
            ->toArray();
    }

    /**
     * Get all unique languages that have labels defined.
     */
    public static function getAvailableLanguages(): array
    {
        return static::distinct()->pluck('language')->toArray();
    }

    /**
     * Upsert a label (create or update).
     */
    public static function setLabel(string $language, string $key, string $value): static
    {
        return static::updateOrCreate(
            ['language' => $language, 'label_key' => $key],
            ['label_value' => $value]
        );
    }
}
