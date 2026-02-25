<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ticket_id', 'user_id', 'message', 'is_internal', 'attachments',
    ];

    protected $casts = [
        'is_internal'  => 'boolean',
        'attachments'  => 'array',
        'created_at'   => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }
}
