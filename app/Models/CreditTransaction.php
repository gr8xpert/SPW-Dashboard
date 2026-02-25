<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'client_id', 'ticket_id', 'user_id', 'type',
        'hours', 'rate', 'description', 'balance_after',
    ];

    protected $casts = [
        'hours'         => 'decimal:2',
        'rate'          => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at'    => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopePurchases($query)
    {
        return $query->where('type', 'purchase');
    }

    public function scopeDeductions($query)
    {
        return $query->where('type', 'deduction');
    }

    public function isCredit(): bool
    {
        return $this->hours > 0;
    }

    public function isDebit(): bool
    {
        return $this->hours < 0;
    }
}
