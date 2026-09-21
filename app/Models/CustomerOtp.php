<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

// Global (not tenant-scoped) sibling of LoyaltyOtp for the customer portal login.
class CustomerOtp extends Model
{
    public const TTL_MINUTES   = 10;
    public const MAX_ATTEMPTS  = 5;
    public const RESEND_WINDOW = 60; // seconds between requests for one phone
    public const HOURLY_CAP    = 3;  // max codes per phone per hour

    protected $fillable = [
        'customer_id', 'phone', 'channel',
        'code_hash', 'attempts', 'ip', 'expires_at', 'verified_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
        'attempts'    => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Returns [$otp, $plainCode] — the caller delivers $plainCode over the channel.
    public static function issue(?Customer $customer, string $phone, string $channel, ?string $ip): array
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = static::create([
            'customer_id' => $customer?->id,
            'phone'       => $phone,
            'channel'     => $channel,
            'code_hash'   => Hash::make($code),
            'attempts'    => 0,
            'ip'          => $ip,
            'expires_at'  => now()->addMinutes(self::TTL_MINUTES),
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

    // Returns true only once; increments attempts on failure, stamps verified_at on success.
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
