<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AutomationEnrollment extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'client_id', 'automation_id', 'contact_id', 'current_step',
        'status', 'next_action_at', 'enrolled_at', 'completed_at',
    ];

    protected $casts = [
        'next_action_at' => 'datetime',
        'enrolled_at'    => 'datetime',
        'completed_at'   => 'datetime',
    ];

    public function automation()
    {
        return $this->belongsTo(Automation::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
