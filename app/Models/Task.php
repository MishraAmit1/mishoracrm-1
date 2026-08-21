<?php

namespace App\Models;

use App\BelongsToTenant;
use App\HasAuditLog;
use App\HasCustomFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes, BelongsToTenant, HasAuditLog, HasCustomFields;

    public static string $customFieldModule = 'task';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'status',
        'priority',
        'tags',
        'taskable_type',
        'taskable_id',
        'assigned_to',
        'created_by',
        'due_at',
        'completed_at',
        'due_notified_at',
        'recurrence_type',
        'recurrence_interval',
        'recurrence_end_date',
        'recurrence_parent_id',
        'estimated_hours',
        'actual_hours',
    ];

    protected $casts = [
        'due_at'               => 'date',
        'completed_at'         => 'date',
        'due_notified_at'      => 'datetime',
        'tags'                 => 'array',
        'recurrence_interval'  => 'integer',
        'recurrence_end_date'  => 'date',
        'estimated_hours'      => 'decimal:2',
        'actual_hours'         => 'decimal:2',
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

    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class)->orderBy('position');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class)->latest('started_at');
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_watchers')->withTimestamps();
    }

    public function recurrenceParent()
    {
        return $this->belongsTo(Task::class, 'recurrence_parent_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Task::class, 'recurrence_parent_id');
    }

    // Tasks that must be completed before this one can start ("blocked by")
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')
            ->withTimestamps();
    }

    // Tasks that are waiting on this one ("blocks")
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')
            ->withTimestamps();
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeDueForReminder($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_at')
            ->whereNull('due_notified_at')
            ->whereDate('due_at', '<=', now());
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->due_at
            && !in_array($this->status, ['completed', 'cancelled'])
            && $this->due_at->isPast();
    }

    public function getChecklistProgressAttribute(): array
    {
        $total = $this->checklistItems->count();
        $done  = $this->checklistItems->where('is_done', true)->count();

        return ['done' => $done, 'total' => $total];
    }

    public function isRecurring(): bool
    {
        return in_array($this->recurrence_type, ['daily', 'weekly', 'monthly'], true);
    }

    // Sums all logged time entries (completed ones only — a still-running
    // timer's elapsed time isn't counted until it's stopped) and writes the
    // total to actual_hours, keeping the existing tasks/show.blade.php
    // "Est. Xh · Actual Yh" display accurate.
    public function recalculateActualHours(): void
    {
        $totalMinutes = $this->timeEntries()->whereNotNull('duration_minutes')->sum('duration_minutes');

        $this->update(['actual_hours' => $totalMinutes > 0 ? round($totalMinutes / 60, 2) : null]);
    }

    public function hasIncompleteDependencies(): bool
    {
        return $this->dependencies()->where('status', '!=', 'completed')->exists();
    }

    // ── Would linking $this -> depends on $targetTaskId create a cycle?
    // True if $targetTaskId already (directly or transitively) depends on $this.
    public function wouldCreateCycle(int $targetTaskId): bool
    {
        if ($targetTaskId === $this->id) {
            return true;
        }

        $visited = [];
        $queue   = [$targetTaskId];

        while ($queue) {
            $current = array_pop($queue);
            if (in_array($current, $visited, true)) continue;
            $visited[] = $current;

            $upstream = static::find($current)?->dependencies()->pluck('tasks.id')->all() ?? [];

            foreach ($upstream as $id) {
                if ($id === $this->id) return true;
                $queue[] = $id;
            }
        }

        return false;
    }

    // ── Creates the next occurrence when a recurring task is completed.
    // Editing/deleting one occurrence never touches the others — each is
    // an independent row linked only via recurrence_parent_id.
    public function createNextOccurrence(): ?self
    {
        if (!$this->isRecurring() || !$this->due_at) {
            return null;
        }

        $nextDue = match ($this->recurrence_type) {
            'daily'   => $this->due_at->copy()->addDays($this->recurrence_interval),
            'weekly'  => $this->due_at->copy()->addWeeks($this->recurrence_interval),
            'monthly' => $this->due_at->copy()->addMonths($this->recurrence_interval),
            default   => null,
        };

        if (!$nextDue || ($this->recurrence_end_date && $nextDue->gt($this->recurrence_end_date))) {
            return null;
        }

        return static::create([
            'tenant_id'             => $this->tenant_id,
            'title'                 => $this->title,
            'description'           => $this->description,
            'status'                => 'pending',
            'priority'              => $this->priority,
            'tags'                  => $this->tags,
            'taskable_type'         => $this->taskable_type,
            'taskable_id'           => $this->taskable_id,
            'assigned_to'           => $this->assigned_to,
            'created_by'            => $this->created_by,
            'due_at'                => $nextDue,
            'recurrence_type'       => $this->recurrence_type,
            'recurrence_interval'   => $this->recurrence_interval,
            'recurrence_end_date'   => $this->recurrence_end_date,
            'recurrence_parent_id'  => $this->recurrence_parent_id ?? $this->id,
        ]);
    }
}
