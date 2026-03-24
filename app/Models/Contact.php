<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Contact extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'client_id', 'email', 'first_name', 'last_name', 'phone',
        'company', 'tags', 'custom_fields', 'lead_score', 'engagement_tier',
        'status', 'consent_date', 'consent_source', 'source', 'timezone',
        'double_opt_in_token', 'unsubscribe_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (Contact $contact) {
            if (empty($contact->double_opt_in_token)) {
                $contact->double_opt_in_token = Str::random(64);
            }
        });
    }

    protected $casts = [
        'tags'                          => 'array',
        'custom_fields'                 => 'array',
        'consent_date'                  => 'datetime',
        'last_opened_at'                => 'datetime',
        'last_clicked_at'               => 'datetime',
        'last_emailed_at'               => 'datetime',
        'double_opt_in_confirmed_at'    => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function lists()
    {
        return $this->belongsToMany(ContactList::class, 'contact_list_pivot', 'contact_id', 'list_id')
                    ->withPivot('added_at', 'client_id');
    }

    public function emailEvents()
    {
        return $this->hasMany(EmailEvent::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: $this->email;
    }
}
