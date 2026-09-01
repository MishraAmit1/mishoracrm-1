<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\BelongsToTenant;

class WhatsappChatbotFlow extends Model
{
    use BelongsToTenant;

    public const ACTIONS = ['loyalty_join'];

    protected $fillable = [
        'tenant_id',
        'name',
        'trigger_keywords',
        'keyword_match',
        'response_message',
        'action',
        'quick_replies',
        'is_default',
        'is_active',
        'triggered_count',
        'sort_order',
    ];

    protected $casts = [
        'trigger_keywords' => 'array',
        'quick_replies'    => 'array',
        'is_default'       => 'boolean',
        'is_active'        => 'boolean',
        'triggered_count'  => 'integer',
    ];

    public function matches(string $text): bool
    {
        $keywords = $this->trigger_keywords ?? [];
        if (empty($keywords)) return $this->is_default;

        $text = mb_strtolower(trim($text));

        foreach ($keywords as $keyword) {
            $keyword = mb_strtolower(trim($keyword));

            $matched = match ($this->keyword_match) {
                'exact'  => $text === $keyword,
                'any'    => str_contains($text, $keyword),
                default  => str_contains($text, $keyword),
            };

            if ($matched) return true;
        }

        return false;
    }

    public function incrementTriggered(): void
    {
        $this->increment('triggered_count');
    }
}
