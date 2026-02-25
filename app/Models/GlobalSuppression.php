<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalSuppression extends Model
{
    public $timestamps = false;

    protected $fillable = ['email', 'reason', 'source_client_id', 'added_at'];

    protected $casts = [
        'added_at' => 'datetime',
    ];
}
