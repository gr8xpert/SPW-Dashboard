<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    /**
     * Log an audit event with full context.
     */
    public static function log(
        string $action,
        ?string $resourceType = null,
        ?int $resourceId = null,
        array $details = [],
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $clientId = null
    ): void {
        AuditLog::create([
            'client_id'       => $clientId ?? auth()->user()?->client_id,
            'user_id'         => auth()->id(),
            'action'          => $action,
            'entity_type'     => $resourceType,
            'entity_id'       => $resourceId,
            'resource_type'   => $resourceType,
            'resource_id'     => $resourceId,
            'details'         => $details,
            'old_values'      => $oldValues,
            'new_values'      => $newValues,
            'ip_address'      => request()->ip(),
            'user_agent'      => request()->userAgent(),
            'impersonated_by' => session('impersonated_by'),
        ]);
    }

    /**
     * Log a model change with before/after values.
     */
    public static function logModelChange(string $action, $model, array $oldValues = []): void
    {
        $newValues = $model->getChanges();

        // Only log if something actually changed
        if (empty($newValues)) return;

        self::log(
            $action,
            class_basename($model),
            $model->id,
            [],
            array_intersect_key($oldValues, $newValues),
            $newValues,
            $model->client_id ?? null
        );
    }
}
