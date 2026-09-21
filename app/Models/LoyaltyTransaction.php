<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One immutable row in the Customer Loyalty ledger. See the
 * create_loyalty_transactions_table migration for the lot/FIFO design.
 * All writes go through App\Services\LoyaltyService — never mass-assigned
 * from a request.
 */
class LoyaltyTransaction extends Model
{
    use BelongsToTenant;

    public const TYPE_EARN   = 'earn';
    public const TYPE_REDEEM = 'redeem';
    public const TYPE_EXPIRE = 'expire';
    public const TYPE_ADJUST = 'adjust';

    // Stamp-card rows share this ledger but never touch the points balance:
    // `points` holds the signed STAMP delta, `balance_after` the card progress.
    public const TYPE_STAMP        = 'stamp';        // +n earned / -n expired or reversed
    public const TYPE_STAMP_REWARD = 'stamp_reward'; // +1 card completed / -1 reward claimed

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'type',
        'points',
        'balance_after',
        'remaining_points',
        'earn_expires_at',
        'expiry_reminded_at',
        'description',
        'source_type',
        'source_id',
        'created_by',
    ];

    protected $casts = [
        'points'             => 'integer',
        'balance_after'      => 'integer',
        'remaining_points'   => 'integer',
        'earn_expires_at'    => 'datetime',
        'expiry_reminded_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Open point lots (earn rows and positive adjustments), oldest first —
    // the order redeem / expire draw them down. Rows that carry no unspent
    // balance (redeem, expire, negative adjust) always have remaining_points 0.
    public function scopeOpenLots($query)
    {
        return $query->where('remaining_points', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isCredit(): bool
    {
        return $this->points > 0;
    }
}
