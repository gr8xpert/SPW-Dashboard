<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientDomain extends Model
{
    protected $fillable = [
        'client_id', 'domain', 'is_primary', 'verified',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'verified'   => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }
}
