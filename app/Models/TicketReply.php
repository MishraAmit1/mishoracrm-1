<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReply extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'ticket_id',
        'user_id',
        'author_name',
        'is_customer_reply',
        'is_internal_note',
        'body',
    ];

    protected $casts = [
        'is_customer_reply' => 'boolean',
        'is_internal_note'  => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authorLabel(): string
    {
        return $this->is_customer_reply
            ? ($this->author_name ?: 'Customer')
            : ($this->user?->name ?? 'Staff');
    }
}
