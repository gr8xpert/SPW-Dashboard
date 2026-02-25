<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, BelongsToTenant;

    protected $fillable = [
        'client_id', 'name', 'email', 'password', 'role',
        'two_factor_secret', 'two_factor_enabled', 'status',
        'last_login_at', 'last_login_ip',
        'failed_login_attempts', 'locked_until',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at'   => 'datetime',
        'last_login_at'       => 'datetime',
        'locked_until'        => 'datetime',
        'two_factor_enabled'  => 'boolean',
        'password'            => 'hashed',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedTickets()
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }

    public function createdTickets()
    {
        return $this->hasMany(SupportTicket::class, 'created_by');
    }

    public function ticketMessages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    public function isEditor(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'editor']);
    }

    public function isWebmaster(): bool
    {
        return $this->role === 'webmaster';
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }
}
