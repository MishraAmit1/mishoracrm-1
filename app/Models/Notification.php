<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'triggered_by',
        'type', 'title', 'message', 'url',
        'icon', 'color',
        'notifiable_type', 'notifiable_id',
        'is_read', 'read_at',
        'channels_sent', 'channel_status', 'channel_errors',
    ];

    protected $casts = [
        'is_read'        => 'boolean',
        'read_at'        => 'datetime',
        'channels_sent'  => 'array',
        'channel_status' => 'array',
        'channel_errors' => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function getIconSvgAttribute(): string
    {
        $icons = config('notifications.icons', []);
        $key   = $this->icon ?? 'bell';
        return $icons[$key] ?? $icons['bell'];
    }

    public function getTypeConfigAttribute(): array
    {
        return config('notifications.types')[$this->type] ?? [
            'label' => ucfirst($this->type),
            'color' => 'accent',
            'icon'  => 'bell',
        ];
    }
}