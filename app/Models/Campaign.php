<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'client_id', 'name', 'subject', 'subject_b', 'preview_text',
        'from_name', 'from_email', 'reply_to', 'template_id', 'list_id',
        'segment_rules', 'html_content', 'plain_text_content', 'smtp_account_id',
        'status', 'ab_test_enabled', 'ab_test_percentage', 'ab_winner_criteria',
        'ab_test_duration_hours', 'scheduled_at', 'started_at', 'completed_at',
        'spam_score', 'created_by',
    ];

    protected $casts = [
        'segment_rules'    => 'array',
        'ab_test_enabled'  => 'boolean',
        'pre_rendered'     => 'boolean',
        'scheduled_at'     => 'datetime',
        'started_at'       => 'datetime',
        'completed_at'     => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function list()
    {
        return $this->belongsTo(ContactList::class, 'list_id');
    }

    public function smtpAccount()
    {
        return $this->belongsTo(SmtpAccount::class);
    }

    public function sends()
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function events()
    {
        return $this->hasMany(EmailEvent::class);
    }

    public function queue()
    {
        return $this->hasOne(CampaignQueue::class);
    }

    public function getOpenRateAttribute(): float
    {
        if ($this->total_sent === 0) return 0;
        return round(($this->total_opened / $this->total_sent) * 100, 1);
    }

    public function getClickRateAttribute(): float
    {
        if ($this->total_sent === 0) return 0;
        return round(($this->total_clicked / $this->total_sent) * 100, 1);
    }

    public function getBounceRateAttribute(): float
    {
        if ($this->total_sent === 0) return 0;
        return round(($this->total_bounced / $this->total_sent) * 100, 1);
    }
}
