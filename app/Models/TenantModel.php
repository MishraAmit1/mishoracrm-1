<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantModel extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant_id')) {
                $query->where('tenant_id', app('tenant_id'));
            }
        });
    }
}
