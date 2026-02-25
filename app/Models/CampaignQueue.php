<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CampaignQueue extends Model
{
    use BelongsToTenant;

    protected $table = 'campaign_queue';
    public $timestamps = false;

    protected $fillable = [
        'client_id', 'campaign_id', 'priority', 'status',
        'batch_size', 'current_offset', 'total_to_send',
        'started_at', 'completed_at', 'last_batch_at', 'error_message',
    ];

    protected $casts = [
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
        'last_batch_at' => 'datetime',
        'created_at'    => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
