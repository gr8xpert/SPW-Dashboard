<?php

namespace App\Services;

use App\Models\Contact;

/**
 * Shared service for personalizing email content with contact data.
 * Used by EmailContentService and AutomationService.
 */
class PersonalizationService
{
    /**
     * Available merge tags and their contact field mappings.
     */
    protected array $mergeTags = [
        '{{first_name}}' => 'first_name',
        '{{last_name}}'  => 'last_name',
        '{{email}}'      => 'email',
        '{{company}}'    => 'company',
        '{{full_name}}'  => 'full_name',
        '{{phone}}'      => 'phone',
    ];

    /**
     * Personalize content by replacing merge tags with contact data.
     */
    public function personalize(string $content, Contact $contact): string
    {
        $replacements = [];

        foreach ($this->mergeTags as $tag => $field) {
            $replacements[$tag] = $contact->{$field} ?? '';
        }

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );
    }

    /**
     * Get list of available merge tags for documentation/UI.
     */
    public function getAvailableTags(): array
    {
        return array_keys($this->mergeTags);
    }
}
