<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'client_id', 'user_id', 'action', 'entity_type',
        'entity_id', 'resource_type', 'resource_id',
        'details', 'old_values', 'new_values',
        'ip_address', 'user_agent', 'impersonated_by',
    ];

    protected $casts = [
        'details'    => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function impersonator()
    {
        return $this->belongsTo(User::class, 'impersonated_by');
    }

    /**
     * Log an audit event.
     */
    public static function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        array $details = [],
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        static::create([
            'action'          => $action,
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
            'resource_type'   => $entityType,
            'resource_id'     => $entityId,
            'details'         => $details,
            'old_values'      => $oldValues,
            'new_values'      => $newValues,
            'ip_address'      => request()->ip(),
            'user_agent'      => request()->userAgent(),
            'user_id'         => auth()->id(),
            'impersonated_by' => session('impersonated_by'),
        ]);
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForResource($query, string $type, ?int $id = null)
    {
        $query->where('resource_type', $type);
        if ($id) $query->where('resource_id', $id);
        return $query;
    }
}
