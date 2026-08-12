<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskTemplate extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'default_priority',
        'default_tags',
        'checklist_items',
        'created_by',
    ];

    protected $casts = [
        'default_tags'    => 'array',
        'checklist_items' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Build a Task fillable array + checklist items from this template ──
    public function toTaskData(): array
    {
        return [
            'title'       => $this->name,
            'description' => $this->description,
            'priority'    => $this->default_priority,
            'tags'        => $this->default_tags,
        ];
    }
}
