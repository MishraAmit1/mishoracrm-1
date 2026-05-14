<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // ── Auto set tenant_id on create ──────────────────────────
        static::creating(function ($model) {
            // 1. Pehle auth user ka tenant_id lo (sabse reliable)
            if (empty($model->tenant_id) && auth()->check() && auth()->user()->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
                return;
            }

            // 2. app() container se lo (middleware set karta hai)
            if (empty($model->tenant_id) && app()->has('tenant_id')) {
                $model->tenant_id = app('tenant_id');
                return;
            }

            // 3. session se lo (fallback)
            if (empty($model->tenant_id) && session()->has('tenant_id')) {
                $model->tenant_id = session('tenant_id');
            }
        });

        // ── Auto filter by tenant_id on every query ───────────────
        static::addGlobalScope('tenant', function (Builder $query) {
            // SuperAdmin ke liye scope skip karo
            if (auth()->check() && auth()->user()->isSuperAdmin()) {
                return;
            }

            // Auth user ka tenant_id use karo
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where(
                    $query->getModel()->getTable() . '.tenant_id',
                    auth()->user()->tenant_id
                );
                return;
            }

            // app() container fallback
            if (app()->has('tenant_id')) {
                $query->where(
                    $query->getModel()->getTable() . '.tenant_id',
                    app('tenant_id')
                );
            }
        });
    }


    // ── Manual scope for tenant_id ─────────────────────────────
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}