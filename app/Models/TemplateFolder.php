<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TemplateFolder extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = ['client_id', 'name', 'parent_folder_id'];

    public function templates()
    {
        return $this->hasMany(Template::class, 'folder_id');
    }

    public function parent()
    {
        return $this->belongsTo(TemplateFolder::class, 'parent_folder_id');
    }

    public function children()
    {
        return $this->hasMany(TemplateFolder::class, 'parent_folder_id');
    }
}
