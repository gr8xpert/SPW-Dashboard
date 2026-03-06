<?php

namespace App\Services;

use App\Models\DefaultLabel;
use App\Models\ClientLabelOverride;

class LabelService
{
    /**
     * Language code mappings for fallback support.
     * Handles short codes (en) to full locale codes (en_US).
     */
    protected array $languageFallbacks = [
        'en' => 'en_US',
        'es' => 'es_ES',
        'de' => 'de_DE',
        'fr' => 'fr_FR',
        'it' => 'it_IT',
        'nl' => 'nl_NL',
        'pt' => 'pt_PT',
        'ru' => 'ru_RU',
        'sv' => 'sv_SE',
        'da' => 'da_DK',
        'no' => 'no_NO',
        'fi' => 'fi_FI',
        'pl' => 'pl_PL',
    ];

    /**
     * Normalize language code to full locale format.
     */
    protected function normalizeLanguage(string $language): string
    {
        // If it's a short code, map to full locale
        if (isset($this->languageFallbacks[$language])) {
            return $this->languageFallbacks[$language];
        }

        // Already a full locale code
        return $language;
    }

    /**
     * Get merged labels for a client and language.
     * Priority: Client overrides > Default labels
     */
    public function getMergedLabels(int $clientId, string $language): array
    {
        $language = $this->normalizeLanguage($language);

        // Get default labels for the language
        $defaults = DefaultLabel::getLabelsForLanguage($language);

        // Get client overrides
        $overrides = ClientLabelOverride::getOverridesForClient($clientId, $language);

        // Merge: overrides take precedence
        return array_merge($defaults, $overrides);
    }

    /**
     * Get labels with metadata showing which are overridden.
     * Useful for the client dashboard UI.
     */
    public function getLabelsWithMetadata(int $clientId, string $language): array
    {
        $language = $this->normalizeLanguage($language);

        $defaults = DefaultLabel::getLabelsForLanguage($language);
        $overrides = ClientLabelOverride::getOverridesForClient($clientId, $language);

        $result = [];
        foreach ($defaults as $key => $value) {
            $result[$key] = [
                'default_value' => $value,
                'current_value' => $overrides[$key] ?? $value,
                'is_overridden' => isset($overrides[$key]),
            ];
        }

        return $result;
    }

    /**
     * Get all available languages from default labels.
     */
    public function getAvailableLanguages(): array
    {
        return DefaultLabel::getAvailableLanguages();
    }

    /**
     * Set a default label (Super Admin).
     */
    public function setDefaultLabel(string $language, string $key, string $value): DefaultLabel
    {
        return DefaultLabel::setLabel($language, $key, $value);
    }

    /**
     * Set a client label override.
     */
    public function setClientOverride(int $clientId, string $language, string $key, string $value): ClientLabelOverride
    {
        $language = $this->normalizeLanguage($language);
        return ClientLabelOverride::setOverride($clientId, $language, $key, $value);
    }

    /**
     * Remove a client label override (revert to default).
     */
    public function removeClientOverride(int $clientId, string $language, string $key): bool
    {
        $language = $this->normalizeLanguage($language);
        return ClientLabelOverride::removeOverride($clientId, $language, $key);
    }

    /**
     * Bulk import labels for a language (used for seeding from labels.ts).
     */
    public function bulkImportDefaults(string $language, array $labels): int
    {
        $count = 0;
        foreach ($labels as $key => $value) {
            DefaultLabel::setLabel($language, $key, $value);
            $count++;
        }
        return $count;
    }
}
