<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowRequest extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'workflow_template_id',
        'business_type', 'problem_description',
        'contact_preference', 'contact_value',
        'status', 'admin_notes',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'workflow_template_id');
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }
}
