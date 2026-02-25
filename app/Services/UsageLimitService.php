<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientUsage;
use Illuminate\Support\Facades\Auth;

class UsageLimitService
{
    public function canAddContact(Client $client): bool
    {
        $usage = $this->getOrCreateUsage($client);
        return $usage->contacts_count < $client->plan->max_contacts;
    }

    public function canSendEmails(Client $client, int $count = 1): bool
    {
        $usage = $this->getOrCreateUsage($client);
        return ($usage->emails_sent + $count) <= $client->plan->max_emails_per_month;
    }

    public function canCreateTemplate(Client $client): bool
    {
        $templateCount = \App\Models\Template::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('client_id', $client->id)
            ->count();
        return $templateCount < $client->plan->max_templates;
    }

    public function canInviteUser(Client $client): bool
    {
        $userCount = \App\Models\User::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('client_id', $client->id)
            ->count();
        return $userCount < $client->plan->max_users;
    }

    public function canUseAI(Client $client): bool
    {
        $features = $client->plan->features;
        $limit = $features['ai_generation_monthly'] ?? 0;
        if ($limit >= 999999) return true;

        $usage = $this->getOrCreateUsage($client);
        return $usage->ai_generations < $limit;
    }

    public function canUploadImage(Client $client, float $fileSizeMb): bool
    {
        $usage = $this->getOrCreateUsage($client);
        return ($usage->image_storage_used_mb + $fileSizeMb) <= $client->plan->max_image_storage_mb;
    }

    public function incrementEmailsSent(Client $client, int $count = 1): void
    {
        $this->getOrCreateUsage($client)->increment('emails_sent', $count);
    }

    public function incrementAiGenerations(Client $client): void
    {
        $this->getOrCreateUsage($client)->increment('ai_generations');
    }

    public function incrementContacts(Client $client, int $count = 1): void
    {
        $this->getOrCreateUsage($client)->increment('contacts_count', $count);
    }

    public function decrementContacts(Client $client, int $count = 1): void
    {
        $this->getOrCreateUsage($client)->decrement('contacts_count', $count);
    }

    public function getPlanFeature(Client $client, string $feature, $default = false)
    {
        return $client->plan->features[$feature] ?? $default;
    }

    protected function getOrCreateUsage(Client $client): ClientUsage
    {
        $month = now()->format('Y-m');
        return ClientUsage::firstOrCreate(
            ['client_id' => $client->id, 'month' => $month],
            ['emails_sent' => 0, 'contacts_count' => 0, 'ai_generations' => 0]
        );
    }
}
