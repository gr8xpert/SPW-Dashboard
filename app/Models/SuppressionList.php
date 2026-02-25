<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SuppressionList extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'suppression_lists';

    protected $fillable = ['client_id', 'email', 'reason', 'campaign_id', 'added_at'];

    protected $casts = [
        'added_at' => 'datetime',
    ];
}
