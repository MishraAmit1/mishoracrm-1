<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = [

        'tenant_id',

        'user_id',

        'device_token',

        'device_id',

        'device_name',

        'platform',

        'app_version',

        'is_active',

        'last_used_at',

    ];
}