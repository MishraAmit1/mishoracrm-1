<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Sales enquiries from the public /contact-sales page (Enterprise "Talk to
// sales" flow). Platform-level — no tenant scope.
class ContactEnquiry extends Model
{
    public const STATUSES = ['new', 'contacted', 'closed'];

    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'team_size',
        'message',
        'status',
        'ip',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'new'       => 'badge-blue',
            'contacted' => 'badge-gray',
            'closed'    => 'badge-green',
            default     => 'badge-gray',
        };
    }
}
