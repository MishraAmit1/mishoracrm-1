<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TenantIntegration extends Model
{
    protected $fillable = [
        'tenant_id',
        'platform',
        'is_active',
        'credentials',
        'settings',
        'webhook_token',
        'last_synced_at',
        'leads_imported',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'settings'       => 'array',
        'last_synced_at' => 'datetime',
        'leads_imported' => 'integer',
    ];

    // Supported platforms list
    public const PLATFORMS = [
        'meta_lead_ads' => [
            'label'   => 'Meta Lead Ads',
            'icon'    => 'fab fa-facebook',
            'color'   => '#1877F2',
            'type'    => 'webhook',    // webhook | polling
            'source'  => 'facebook',  // maps to Lead::source
        ],
        'indiamart' => [
            'label'   => 'IndiaMART',
            'icon'    => 'fas fa-store',
            'color'   => '#007DC5',
            'type'    => 'polling',
            'source'  => 'indiamart',
        ],
        'justdial' => [
            'label'   => 'JustDial',
            'icon'    => 'fas fa-phone-alt',
            'color'   => '#FF6600',
            'type'    => 'webhook',
            'source'  => 'justdial',
        ],
        'tradeindia' => [
            'label'   => 'TradeIndia',
            'icon'    => 'fas fa-globe',
            'color'   => '#E31E24',
            'type'    => 'webhook',
            'source'  => 'tradeindia',
        ],
        'sulekha' => [
            'label'   => 'Sulekha',
            'icon'    => 'fas fa-handshake',
            'color'   => '#FF5000',
            'type'    => 'webhook',
            'source'  => 'sulekha',
        ],
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Credentials (encrypted) ───────────────────────────────────

    public function getCredentialsAttribute(?string $value): array
    {
        if (empty($value)) return [];
        try {
            return json_decode(Crypt::decryptString($value), true) ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function setCredentialsAttribute(array $value): void
    {
        $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
    }

    public function getCredential(string $key, mixed $default = null): mixed
    {
        return $this->credentials[$key] ?? $default;
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getPlatformInfo(): array
    {
        return self::PLATFORMS[$this->platform] ?? ['label' => $this->platform, 'icon' => 'fas fa-plug', 'color' => '#666', 'type' => 'webhook', 'source' => 'other'];
    }

    public function getLabel(): string
    {
        return self::PLATFORMS[$this->platform]['label'] ?? ucfirst($this->platform);
    }

    public function isWebhook(): bool
    {
        return ($this->getPlatformInfo()['type'] ?? 'webhook') === 'webhook';
    }

    public function isPolling(): bool
    {
        return ($this->getPlatformInfo()['type'] ?? '') === 'polling';
    }

    public function webhookUrl(): string
    {
        return url("/webhook/leads/{$this->webhook_token}");
    }

    public function regenerateWebhookToken(): void
    {
        $this->update(['webhook_token' => Str::random(40)]);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    // ── Static Factory ────────────────────────────────────────────

    public static function forTenant(int $tenantId, string $platform): self
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenantId, 'platform' => $platform],
            ['webhook_token' => Str::random(40)]
        );
    }

    // ── Super Admin: allowed integrations for a tenant ────────────
    // Stored in Tenant.settings['integrations'] = ['meta_lead_ads' => true, ...]

    public static function getAllowedPlatforms(Tenant $tenant): array
    {
        $allowed = $tenant->settings['integrations'] ?? [];
        return array_keys(array_filter($allowed));
    }

    public static function isPlatformAllowed(Tenant $tenant, string $platform): bool
    {
        return (bool) ($tenant->settings['integrations'][$platform] ?? false);
    }
}
