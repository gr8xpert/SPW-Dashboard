<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPropertyTypeMapping extends Model
{
    protected $fillable = [
        'client_id',
        'custom_group_id',
        'feed_type_id',
        'feed_type_name',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function customGroup(): BelongsTo
    {
        return $this->belongsTo(ClientCustomPropertyTypeGroup::class, 'custom_group_id');
    }
}
