<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientUsage extends Model
{
    protected $table = 'client_usage';

    protected $fillable = [
        'client_id', 'month', 'emails_sent', 'contacts_count',
        'api_calls', 'ai_generations', 'image_storage_used_mb',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
