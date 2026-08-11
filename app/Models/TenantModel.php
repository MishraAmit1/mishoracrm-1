<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Every current subclass (Lead, Followup) also `use BelongsToTenant`, whose
// global scope is registered under the same 'tenant' key and runs first —
// so it already covers isolation (auth user, app('tenant_id'), session
// fallback, superadmin bypass) and this class must not re-register that key,
// or it silently overwrites the trait's scope with this weaker one.
class TenantModel extends Model
{
    //
}
