<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ContactList extends Model
{
    use BelongsToTenant;

    protected $table = 'contact_lists';

    protected $fillable = [
        'client_id', 'name', 'description', 'type', 'filter_rules', 'contacts_count',
    ];

    protected $casts = [
        'filter_rules' => 'array',
    ];

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_list_pivot', 'list_id', 'contact_id')
                    ->withPivot('added_at');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
