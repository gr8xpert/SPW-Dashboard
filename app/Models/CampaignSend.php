<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CampaignSend extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'client_id', 'campaign_id', 'contact_id', 'ab_variant',
        'personalized_html', 'status', 'sent_at', 'delivered_at',
        'opened_at', 'clicked_at', 'bounced_at', 'bounce_type',
        'bounce_reason', 'retry_count', 'next_retry_at', 'smtp_message_id',
    ];

    protected $casts = [
        'sent_at'       => 'datetime',
        'delivered_at'  => 'datetime',
        'opened_at'     => 'datetime',
        'clicked_at'    => 'datetime',
        'bounced_at'    => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
