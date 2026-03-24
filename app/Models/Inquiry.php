<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'client_id',
        'contact_id',
        'name',
        'email',
        'phone',
        'message',
        'property_ref',
        'property_title',
        'property_url',
        'property_price',
        'status',
        'source',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the property address for display (alias for property_title).
     */
    public function getPropertyAddressAttribute(): ?string
    {
        return $this->property_title;
    }
}
