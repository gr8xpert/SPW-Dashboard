<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationStep extends Model
{
    public $timestamps = false;

    protected $fillable = ['automation_id', 'step_order', 'step_type', 'config'];

    protected $casts = [
        'config'     => 'array',
        'created_at' => 'datetime',
    ];

    public function automation()
    {
        return $this->belongsTo(Automation::class);
    }
}
