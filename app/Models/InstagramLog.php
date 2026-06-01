<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\BelongsToTenant;

class InstagramLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'event_type',
        'instagram_user_id',
        'instagram_username',
        'post_id',
        'comment_id',
        'message_id',
        'incoming_text',
        'outgoing_text',
        'automation_id',
        'chatbot_flow_id',
        'status',
        'error_message',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function automation()
    {
        return $this->belongsTo(InstagramAutomation::class, 'automation_id');
    }

    public function chatbotFlow()
    {
        return $this->belongsTo(InstagramChatbotFlow::class, 'chatbot_flow_id');
    }
}
