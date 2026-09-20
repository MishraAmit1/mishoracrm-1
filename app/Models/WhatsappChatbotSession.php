<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappChatbotSession extends Model
{
    protected $fillable = [
        'tenant_id',
        'wa_id',
        'contact_name',
        'chatbot_flow_id',
        'context',
        'last_message_at',
    ];

    protected $casts = [
        'context'         => 'array',
        'last_message_at' => 'datetime',
    ];

    public function flow()
    {
        return $this->belongsTo(WhatsappChatbotFlow::class, 'chatbot_flow_id');
    }

    public static function getOrCreate(int $tenantId, string $waId, ?string $name = null): self
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenantId, 'wa_id' => $waId],
            ['contact_name' => $name, 'last_message_at' => now()]
        );
    }
}
