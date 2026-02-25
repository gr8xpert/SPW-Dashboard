<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ImageLibrary extends Model
{
    use BelongsToTenant;

    protected $table = 'image_library';

    protected $fillable = [
        'client_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'url',
        'alt_text',
    ];
}
