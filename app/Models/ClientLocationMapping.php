<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientLocationMapping extends Model
{
    protected $fillable = [
        'client_id',
        'custom_group_id',
        'feed_location_id',
        'feed_location_name',
        'feed_location_type',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // --- Relationships ---

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function customGroup(): BelongsTo
    {
        return $this->belongsTo(ClientCustomLocationGroup::class, 'custom_group_id');
    }
}
