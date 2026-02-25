<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class EmailEvent extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'client_id', 'campaign_send_id', 'campaign_id', 'contact_id',
        'event_type', 'link_url', 'user_agent', 'ip_address', 'device_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function campaignSend()
    {
        return $this->belongsTo(CampaignSend::class);
    }
}
