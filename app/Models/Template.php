<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'client_id', 'folder_id', 'name', 'category', 'mode',
        'html_content', 'json_design', 'plain_text_content',
        'thumbnail_path', 'is_locked', 'is_platform_template', 'created_by',
    ];

    protected $casts = [
        'json_design'          => 'array',
        'is_locked'            => 'boolean',
        'is_platform_template' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function folder()
    {
        return $this->belongsTo(TemplateFolder::class, 'folder_id');
    }

    public function versions()
    {
        return $this->hasMany(TemplateVersion::class)->orderByDesc('version_number');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
