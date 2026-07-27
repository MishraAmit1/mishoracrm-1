<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'body', 'category', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class, 'template_id');
    }

    // Replace {{variables}} with actual values in any piece of text
    public static function substituteVariables(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value ?? '', $text);
        }
        return $text;
    }

    public function render(array $data): string
    {
        return self::substituteVariables($this->body, $data);
    }

    public static function categories(): array
    {
        return [
            'general'   => 'General',
            'followup'  => 'Follow-up',
            'reminder'  => 'Reminder',
            'promotion' => 'Promotion',
            'invoice'   => 'Invoice',
            'quotation' => 'Quotation',
        ];
    }

    // Available variables for template
    public static function variables(): array
    {
        return [
            '{{name}}'        => 'Contact / Lead name',
            '{{company}}'     => 'Company name',
            '{{phone}}'       => 'Phone number',
            '{{email}}'       => 'Email address',
            '{{amount}}'      => 'Amount',
            '{{date}}'        => 'Date',
            '{{agent_name}}'  => 'Assigned staff name',
            '{{business}}'    => 'Your business name',
        ];
    }
}