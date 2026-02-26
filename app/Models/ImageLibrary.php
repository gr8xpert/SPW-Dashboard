<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ImageLibrary extends Model
{
    use BelongsToTenant;

    protected $table = 'image_library';

    const UPDATED_AT = null;

    protected $fillable = [
        'client_id',
        'filename',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'alt_text',
        'folder',
    ];

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
