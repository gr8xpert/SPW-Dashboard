<?php

namespace App\Services;

use App\Models\Client;

class TenantService
{
    protected ?Client $client = null;

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function getClientId(): ?int
    {
        return $this->client?->id;
    }
}
