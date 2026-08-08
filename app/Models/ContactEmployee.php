<?php

namespace App\Models;

use App\BelongsToTenant;
use App\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactEmployee extends Model
{
    use SoftDeletes, BelongsToTenant, HasAuditLog;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'name',
        'designation',
        'emails',
        'phones',
        'is_primary',
    ];

    protected $casts = [
        'emails'     => 'array',
        'phones'     => 'array',
        'is_primary' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ContactEmployeeAttachment::class);
    }
}
