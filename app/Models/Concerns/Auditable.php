<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Records create/update/delete/restore events into audit_logs, capturing the
 * changed attributes (old/new), the acting user, IP and user agent.
 *
 * Apply to any model that should be audited (Employee, User, Department, and
 * later LetterRequest/LetterType/Payslip). Sensitive attributes must be listed
 * in the model's $hidden array — they are redacted before being written.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => $model->writeAuditLog('created', [], $model->auditableNew($model->getAttributes())));

        static::updated(function (Model $model) {
            $changes = $model->getChanges();

            // Ignore no-op saves and timestamp-only changes.
            $tracked = array_diff_key($changes, array_flip([
                $model->getUpdatedAtColumn() ?? 'updated_at',
            ]));

            if ($tracked === []) {
                return;
            }

            $old = array_intersect_key($model->getOriginal(), $tracked);

            $model->writeAuditLog('updated', $model->auditableOld($old), $model->auditableNew($tracked));
        });

        static::deleted(function (Model $model) {
            $action = method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
                ? 'force_deleted'
                : 'deleted';

            $model->writeAuditLog($action, $model->auditableOld($model->getOriginal()), []);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $model) => $model->writeAuditLog('restored', [], $model->auditableNew($model->getAttributes())));
        }
    }

    protected function writeAuditLog(string $action, array $oldValues, array $newValues): void
    {
        $request = request();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $this->getMorphClass(),
            'auditable_id' => $this->getKey(),
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * Redact sensitive attributes ($hidden) from audited old values.
     */
    protected function auditableOld(array $attributes): array
    {
        return $this->redactAudited($attributes);
    }

    /**
     * Redact sensitive attributes ($hidden) from audited new values.
     */
    protected function auditableNew(array $attributes): array
    {
        return $this->redactAudited($attributes);
    }

    protected function redactAudited(array $attributes): array
    {
        // Never persist secrets or raw sensitive data into the audit trail.
        $never = ['password', 'remember_token'];
        $redacted = array_merge($this->getHidden(), $never);

        foreach ($attributes as $key => $value) {
            if (in_array($key, $redacted, true)) {
                $attributes[$key] = '********';
            }
        }

        return $attributes;
    }
}
