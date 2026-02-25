<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WidgetAnalytic extends Model
{
    public $timestamps = false;

    protected $table = 'widget_analytics';

    protected $fillable = [
        'client_id', 'event_type', 'event_data',
        'session_id', 'url', 'user_agent', 'ip_address',
    ];

    protected $casts = [
        'event_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeInDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
