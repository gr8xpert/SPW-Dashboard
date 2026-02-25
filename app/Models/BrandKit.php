<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BrandKit extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'client_id', 'logo_url', 'primary_color', 'secondary_color',
        'accent_color', 'font_heading', 'font_body', 'footer_html',
        'social_links', 'company_address',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
