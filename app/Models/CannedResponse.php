<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CannedResponse extends Model
{
    protected $fillable = [
        'title', 'body', 'category', 'created_by',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Replace variables in the response body.
     */
    public function render(array $variables = []): string
    {
        $body = $this->body;
        foreach ($variables as $key => $value) {
            $body = str_replace('{' . $key . '}', $value, $body);
        }
        return $body;
    }
}
