<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $table = 'staff';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'department_id',
        'employee_code',
        'designation',
        'salary',
        'joining_date',
        'employment_type',
    ];

    protected $casts = [
        'salary'       => 'decimal:2',
        'joining_date' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getNameAttribute(): string
    {
        return $this->user->name ?? '—';
    }

    public function getEmailAttribute(): string
    {
        return $this->user->email ?? '—';
    }

    public static function employmentTypes(): array
    {
        return [
            'full_time' => 'Full Time',
            'part_time' => 'Part Time',
            'contract'  => 'Contract',
            'intern'    => 'Intern',
        ];
    }
}