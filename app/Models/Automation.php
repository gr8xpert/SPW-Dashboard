<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'client_id', 'name', 'trigger_type', 'trigger_config', 'status',
    ];

    protected $casts = [
        'trigger_config' => 'array',
    ];

    public function steps()
    {
        return $this->hasMany(AutomationStep::class)->orderBy('step_order');
    }

    public function enrollments()
    {
        return $this->hasMany(AutomationEnrollment::class);
    }
}
