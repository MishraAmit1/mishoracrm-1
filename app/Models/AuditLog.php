<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'model_type',
        'model_id',
        'model_label',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeForModel(Builder $query, string $modelType): Builder
    {
        return $query->where('model_type', $modelType);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getModelShortNameAttribute(): string
    {
        if (!$this->model_type) return '—';
        return class_basename($this->model_type);
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'created'  => 'green',
            'updated'  => 'amber',
            'deleted'  => 'red',
            'restored' => 'blue',
            'login'    => 'accent',
            'logout'   => 'gray',
            default    => 'gray',
        };
    }

    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'created'  => 'plus-circle',
            'updated'  => 'edit-2',
            'deleted'  => 'trash-2',
            'restored' => 'rotate-ccw',
            'login'    => 'log-in',
            'logout'   => 'log-out',
            default    => 'activity',
        };
    }

    public function getChangedFieldsAttribute(): array
    {
        if (!$this->old_values || !$this->new_values) return [];

        $changed = [];
        foreach ($this->new_values as $key => $newVal) {
            $oldVal = $this->old_values[$key] ?? null;
            if ($oldVal !== $newVal) {
                $changed[] = [
                    'field' => $key,
                    'old'   => $oldVal,
                    'new'   => $newVal,
                ];
            }
        }
        return $changed;
    }

    // ── Static factory ────────────────────────────────────────────

    public static function record(array $data): void
    {
        static::create(array_merge([
            'tenant_id'  => auth()->check() ? auth()->user()->tenant_id : null,
            'user_id'    => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ], $data));
    }
}
