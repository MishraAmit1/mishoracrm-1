<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'status',
        'priority',
        'taskable_type',
        'taskable_id',
        'assigned_to',
        'due_at',
        'completed_at',
        'contact_id',
        'lead_id',
        'deal_id',
    ];

    protected $casts = [
        'due_at'       => 'date',
        'completed_at' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function taskable()
    {
        return $this->morphTo();
    }

    
}
