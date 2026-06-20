<?php

namespace App;

use App\Models\AuditLog;

trait HasAuditLog
{
    // Fields to never store in audit logs
    protected static array $auditExclude = [
        'password', 'remember_token', 'two_factor_secret',
        'two_factor_recovery_codes', 'updated_at',
    ];

    protected static function bootHasAuditLog(): void
    {
        static::created(function ($model) {
            AuditLog::record([
                'action'      => 'created',
                'model_type'  => get_class($model),
                'model_id'    => $model->getKey(),
                'model_label' => $model->getAuditLabel(),
                'new_values'  => $model->getAuditAttributes(),
                'description' => static::getAuditDescription('created', $model),
            ]);
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            $dirty = array_diff_key($dirty, array_flip(static::$auditExclude));

            if (empty($dirty)) return;

            $oldValues = [];
            $newValues = [];
            foreach (array_keys($dirty) as $key) {
                $oldValues[$key] = $model->getOriginal($key);
                $newValues[$key] = $model->getAttribute($key);
            }

            AuditLog::record([
                'action'      => 'updated',
                'model_type'  => get_class($model),
                'model_id'    => $model->getKey(),
                'model_label' => $model->getAuditLabel(),
                'old_values'  => $oldValues,
                'new_values'  => $newValues,
                'description' => static::getAuditDescription('updated', $model),
            ]);
        });

        static::deleted(function ($model) {
            AuditLog::record([
                'action'      => 'deleted',
                'model_type'  => get_class($model),
                'model_id'    => $model->getKey(),
                'model_label' => $model->getAuditLabel(),
                'description' => static::getAuditDescription('deleted', $model),
            ]);
        });

        // SoftDeletes restore support
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                AuditLog::record([
                    'action'      => 'restored',
                    'model_type'  => get_class($model),
                    'model_id'    => $model->getKey(),
                    'model_label' => $model->getAuditLabel(),
                    'description' => static::getAuditDescription('restored', $model),
                ]);
            });
        }
    }

    public function getAuditLabel(): string
    {
        // Override in model for custom label
        return $this->name ?? $this->title ?? $this->phone ?? ((string) $this->getKey());
    }

    protected function getAuditAttributes(): array
    {
        $attrs = $this->getAttributes();
        return array_diff_key($attrs, array_flip(static::$auditExclude));
    }

    protected static function getAuditDescription(string $action, $model): string
    {
        $label = class_basename(get_class($model));
        $name  = $model->getAuditLabel();

        return match ($action) {
            'created'  => "{$label} \"{$name}\" created",
            'updated'  => "{$label} \"{$name}\" updated",
            'deleted'  => "{$label} \"{$name}\" deleted",
            'restored' => "{$label} \"{$name}\" restored",
            default    => "{$label} {$action}",
        };
    }

    public function auditLogs()
    {
        return AuditLog::where('model_type', get_class($this))
            ->where('model_id', $this->getKey())
            ->latest('created_at');
    }
}
