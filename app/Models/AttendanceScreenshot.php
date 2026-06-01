<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AttendanceScreenshot extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'attendance_id',
        'staff_id',
        'path',
        'filename',
        'file_size',
        'captured_at',
        'type',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Helpers ───────────────────────────────────────────────

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024)       return "{$bytes} B";
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
}