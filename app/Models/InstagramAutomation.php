<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\BelongsToTenant;

class InstagramAutomation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'trigger_type',
        'post_id',
        'trigger_keywords',
        'keyword_match',
        'action_type',
        'dm_message',
        'comment_reply',
        'n8n_webhook_url',
        'is_active',
        'triggered_count',
    ];

    protected $casts = [
        'trigger_keywords' => 'array',
        'is_active'        => 'boolean',
        'triggered_count'  => 'integer',
    ];

    public function matchesComment(string $commentText): bool
    {
        if ($this->trigger_type === 'dm_keyword') return false;

        $keywords = $this->trigger_keywords ?? [];
        if (empty($keywords)) return true;

        return $this->matchKeywords($commentText, $keywords);
    }

    public function matchesDm(string $messageText): bool
    {
        if ($this->trigger_type !== 'dm_keyword') return false;

        $keywords = $this->trigger_keywords ?? [];
        if (empty($keywords)) return true;

        return $this->matchKeywords($messageText, $keywords);
    }

    private function matchKeywords(string $text, array $keywords): bool
    {
        $text = mb_strtolower(trim($text));

        foreach ($keywords as $keyword) {
            $keyword = mb_strtolower(trim($keyword));

            $matched = match ($this->keyword_match) {
                'exact'    => $text === $keyword,
                'any'      => str_contains($text, $keyword),
                default    => str_contains($text, $keyword), // contains
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
