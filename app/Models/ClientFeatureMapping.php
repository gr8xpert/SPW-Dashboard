<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientFeatureMapping extends Model
{
    protected $fillable = [
        'client_id',
        'custom_group_id',
        'feed_feature_id',
        'feed_feature_name',
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
        return $this->belongsTo(ClientCustomFeatureGroup::class, 'custom_group_id');
    }
}
