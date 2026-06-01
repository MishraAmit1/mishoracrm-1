<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'type',
        'in_app', 'email', 'whatsapp', 'slack',
    ];

    protected $casts = [
        'in_app'   => 'boolean',
        'email'    => 'boolean',
        'whatsapp' => 'boolean',
        'slack'    => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Get user prefs — with defaults fallback ───────────────────
    public static function getForUser(int $userId, int $tenantId): array
    {
        $saved = static::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('type')
            ->toArray();

        $types    = config('notifications.types', []);
        $channels = config('notifications.channels', []);
        $result   = [];

        foreach ($types as $type => $cfg) {
            $result[$type] = [
                'in_app'   => $saved[$type]['in_app']   ?? true,
                'email'    => $saved[$type]['email']     ?? in_array('email',    $cfg['channels'] ?? []),
                'whatsapp' => $saved[$type]['whatsapp']  ?? in_array('whatsapp', $cfg['channels'] ?? []),
                'slack'    => $saved[$type]['slack']     ?? in_array('slack',    $cfg['channels'] ?? []),
            ];
        }

        return $result;
    }

    // ── Check if specific channel is enabled for a type ───────────
    public static function isEnabled(int $userId, int $tenantId, string $type, string $channel): bool
    {
        $pref = static::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->first();

        if ($pref) {
            return (bool) $pref->$channel;
        }

        // Fallback to config default
        $channels = config("notifications.types.{$type}.channels", ['in_app']);
        return in_array($channel, $channels);
    }
}