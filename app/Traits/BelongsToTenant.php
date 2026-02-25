<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (Auth::check() && empty($model->client_id)) {
                $model->client_id = Auth::user()->client_id;
            }
        });
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
                     ->where('client_id', $clientId);
    }
}
