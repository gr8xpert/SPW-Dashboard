<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'template_id', 'version_number', 'html_content', 'json_design',
        'plain_text_content', 'created_by',
    ];

    protected $casts = [
        'json_design' => 'array',
        'created_at'  => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
