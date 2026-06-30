<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowTemplate extends Model
{
    protected $fillable = [
        'title', 'description', 'category', 'suitable_for',
        'features', 'icon_type', 'color', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'features'  => 'array',
        'is_active' => 'boolean',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(WorkflowRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
