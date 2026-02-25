<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SmtpAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'client_id', 'name', 'provider', 'host', 'port', 'username',
        'password_encrypted', 'encryption', 'from_email', 'from_name',
        'daily_limit', 'hourly_limit', 'is_verified', 'is_default',
    ];

    protected $hidden = ['password_encrypted'];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_default'  => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
