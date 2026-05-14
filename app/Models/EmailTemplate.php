<?php
 
namespace App\Models;
 
use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
 
class EmailTemplate extends Model
{
    use BelongsToTenant;
 
    protected $fillable = [
        'tenant_id', 'name', 'subject', 'body', 'category', 'is_active',
    ];
 
    protected $casts = ['is_active' => 'boolean'];
 
    public function logs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'template_id');
    }
 
    public function render(array $data): array
    {
        $subject = $this->subject;
        $body    = $this->body;
 
        foreach ($data as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', $value, $subject);
            $body    = str_replace('{{' . $key . '}}', $value, $body);
        }
 
        return compact('subject', 'body');
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
 
    public static function variables(): array
    {
        return [
            '{{name}}'       => 'Contact / Lead name',
            '{{company}}'    => 'Company name',
            '{{email}}'      => 'Email address',
            '{{phone}}'      => 'Phone number',
            '{{amount}}'     => 'Amount',
            '{{date}}'       => 'Date',
            '{{agent_name}}' => 'Assigned staff name',
            '{{business}}'   => 'Your business name',
        ];
    }
}
 