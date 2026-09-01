<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class LoyaltyOtp extends Model
{
    use BelongsToTenant;

    public const TTL_MINUTES   = 10;
    public const MAX_ATTEMPTS  = 5;
    public const RESEND_WINDOW = 60; // seconds between requests for one identifier

    protected $fillable = [
        'tenant_id', 'contact_id', 'identifier', 'channel',
        'code_hash', 'attempts', 'ip', 'expires_at', 'verified_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
        'attempts'    => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    // Issue a fresh code for (tenant, contact, identifier). Returns
    // [$otp, $plainCode] — the caller delivers $plainCode over the channel.
    public static function issue(int $tenantId, ?Contact $contact, string $identifier, string $channel, ?string $ip): array
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = static::create([
            'tenant_id'  => $tenantId,
            'contact_id' => $contact?->id,
            'identifier' => $identifier,
            'channel'    => $channel,
            'code_hash'  => Hash::make($code),
            'attempts'   => 0,
            'ip'         => $ip,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return [$otp, $code];
    }

    public function isLocked(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    public function isExpired(): bool
    {
        return !$this->expires_at || $this->expires_at->isPast();
    }

    // Check a submitted code. Returns true only once; increments attempts on
    // failure and stamps verified_at on success.
    public function attempt(string $code): bool
    {
        if ($this->verified_at || $this->isExpired() || $this->isLocked()) {
            return false;
        }

        if (!Hash::check($code, $this->code_hash)) {
            $this->increment('attempts');
            return false;
        }

        $this->forceFill(['verified_at' => now()])->save();

        return true;
    }
}
