<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ViewScope
{
    /**
     * Restrict $query to what $user is allowed to see for $module, based on
     * the spatie permissions "{module}.view_all" / "{module}.view_own".
     *
     *  - has "{module}.view_all"      -> no restriction
     *  - has only "{module}.view_own" -> restricted to $column = user id
     *  - has neither permission       -> matches nothing (secure default)
     */
    public static function apply(Builder $query, string $module, User $user, string $column = 'assigned_to'): Builder
    {
        if ($user->user_type === 'superadmin') {
            return $query;
        }

        if ($user->can("{$module}.view_all")) {
            return $query;
        }

        if ($user->can("{$module}.view_own")) {
            return $query->where($column, $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    /** Convenience for blade/`isAdmin`-style booleans. */
    public static function canViewAll(string $module, User $user): bool
    {
        return $user->user_type === 'superadmin' || $user->can("{$module}.view_all");
    }
}
