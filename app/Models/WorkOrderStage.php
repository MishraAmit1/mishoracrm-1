<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderStage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'work_order_id',
        'sequence',
        'name',
        'assigned_to',
        'status',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'sequence'     => 'integer',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['done', 'skipped'], true);
    }

    public static function statuses(): array
    {
        return [
            'pending'     => 'Pending',
            'in_progress' => 'In Progress',
            'done'        => 'Done',
            'skipped'     => 'Skipped',
        ];
    }
}
