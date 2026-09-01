<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyCampaign extends Model
{
    use BelongsToTenant;

    public const SEGMENTS = ['spend', 'visits', 'inactive', 'tier', 'category', 'manual'];
    public const REWARDS  = ['percent', 'flat', 'points', 'free_item'];

    protected $fillable = [
        'tenant_id', 'created_by', 'name',
        'segment_type', 'segment_config',
        'reward_type', 'reward_value', 'reward_item', 'max_discount',
        'code_mode', 'shared_code', 'expires_at',
        'usage_limit_per_customer', 'total_redemption_cap', 'redeemed_count',
        'delivery', 'status', 'launched_at',
    ];

    protected $casts = [
        'segment_config'           => 'array',
        'reward_value'             => 'decimal:2',
        'max_discount'             => 'decimal:2',
        'expires_at'               => 'datetime',
        'launched_at'              => 'datetime',
        'usage_limit_per_customer' => 'integer',
        'total_redemption_cap'     => 'integer',
        'redeemed_count'           => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(LoyaltyCampaignRecipient::class);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active'
            && (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function isCapReached(): bool
    {
        return $this->total_redemption_cap !== null
            && $this->redeemed_count >= $this->total_redemption_cap;
    }

    public function rewardLabel(): string
    {
        return match ($this->reward_type) {
            'percent'   => rtrim(rtrim(number_format($this->reward_value, 2), '0'), '.') . '% off'
                . ($this->max_discount ? ' (max ₹' . number_format($this->max_discount, 0) . ')' : ''),
            'flat'      => '₹' . number_format($this->reward_value, 0) . ' off',
            'points'    => number_format($this->reward_value, 0) . ' bonus points',
            'free_item' => 'Free ' . ($this->reward_item ?: 'item'),
            default     => '—',
        };
    }
}
