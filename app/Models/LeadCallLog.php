<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadCallLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'type',
        'description',
        'call_outcome',
        'call_duration',
        'logged_at',
        'created_by',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function types(): array
    {
        return [
            'call'      => 'Phone Call',
            'note'      => 'Note',
            'email'     => 'Email',
            'meeting'   => 'Meeting',
            'whatsapp'  => 'WhatsApp',
        ];
    }

    public static function outcomes(): array
    {
        return [
            'connected'      => 'Connected',
            'no_answer'      => 'No Answer',
            'voicemail'      => 'Left Voicemail',
            'callback'       => 'Requested Callback',
            'not_interested' => 'Not Interested',
        ];
    }
}
