<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'is_active',
        'user_type',
        'last_login_at',
        'theme',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function createdLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'created_by');
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    // ── Helper methods ────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->user_type === 'superadmin';
    }

    public function isTenantAdmin(): bool
    {
        return $this->user_type === 'tenant_admin';
    }

    public function isStaff(): bool
    {
        return $this->user_type === 'staff';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar
            ? \Illuminate\Support\Facades\Storage::url($this->avatar)
            : null;
    }
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    // ── Override BelongsToTenant for superadmin ───────────────────
    // Superadmin ke liye tenant scope apply nahi hona chahiye
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if ($model->user_type !== 'superadmin' && app()->has('tenant_id')) {
                $model->tenant_id = $model->tenant_id ?? app('tenant_id');
            }
        });

        // static::addGlobalScope('tenant', function ($query) {
        //     // Superadmin ke liye scope nahi lagayen
        //     if (Auth::check() && Auth::user()?->isSuperAdmin()) {
        //         return;
        //     }
        //     if (app()->has('tenant_id')) {
        //         $query->where('users.tenant_id', app('tenant_id'));
        //     }
        // });

        static::addGlobalScope('tenant', function ($query) {

            // sirf tenant_id pe depend karo
            if (app()->has('tenant_id')) {
                $query->where('users.tenant_id', app('tenant_id'));
            }
        });
    }
}
