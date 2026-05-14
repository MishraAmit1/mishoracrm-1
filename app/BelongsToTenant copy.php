<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Create karte waqt auto tenant_id set karo
        static::creating(function ($model) {
            if (app()->has('tenant_id')) {
                $model->tenant_id = app('tenant_id');
            }
        });

        // Har query mein auto filter
        static::addGlobalScope('tenant', function (Builder $query) {
            if (app()->has('tenant_id')) {
                $query->where($query->getModel()->getTable() . '.tenant_id', app('tenant_id'));
            }
        });
    }

    // Scope manually use karne ke liye
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}