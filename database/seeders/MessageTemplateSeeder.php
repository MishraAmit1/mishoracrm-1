<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\WhatsappTemplate;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // ── Get all tenants ───────────────────────────────────────
        $tenants = \App\Models\Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Run TenantSeeder first.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->seedWhatsappTemplates($tenant->id);
            $this->seedEmailTemplates($tenant->id);
        }

        $this->command->info('Message templates seeded successfully.');
    }

    // ── WhatsApp Templates ────────────────────────────────────────
    private function seedWhatsappTemplates(int $tenantId): void
    {
        $templates = [
            // General
            [
                'name'     => 'Welcome Message',
                'category' => 'general',
                'body'     => "Namaste {{name}} ji! 🙏\n\nAapka {{business}} mein swagat hai.\n\nHum aapki kaise madad kar sakte hain? Koi bhi sawaal ho toh bejhijhak poochhein.\n\nDhanyavaad! 😊",
            ],
            // Follow-up
            [
                'name'     => 'Lead Follow-up',
                'category' => 'followup',
                'body'     => "Hello {{name}} ji,\n\nMain {{agent_name}} bol raha hoon {{business}} se.\n\nKuch din pehle aapne hamse contact kiya tha. Kya aapki query resolve hui?\n\nAgar koi bhi help chahiye ho toh please reply karein. 😊\n\nDhanyavaad!",
            ],
            [
                'name'     => 'Call Follow-up',
                'category' => 'followup',
                'body'     => "Hi {{name}},\n\nAaj humari call ke baad main yeh confirm karna chahta hoon ki aapko sab kuch clearly samajh aaya.\n\nKoi bhi doubt ho toh mujhe WhatsApp karein ya call karein.\n\n{{agent_name}}\n{{business}}",
            ],
            // Reminder
            [
                'name'     => 'Meeting Reminder',
                'category' => 'reminder',
                'body'     => "Namaste {{name}} ji! ⏰\n\nYeh ek reminder hai ki humari meeting {{date}} ko scheduled hai.\n\nKripya samay par available rahein.\n\nDhanyavaad!\n{{business}}",
            ],
            [
                'name'     => 'Payment Reminder',
                'category' => 'reminder',
                'body'     => "Hello {{name}} ji,\n\n💰 Aapka ₹{{amount}} ka payment abhi pending hai.\n\nKripya jaldi se payment complete karein taaki aapki service uninterrupted rahe.\n\nKoi problem ho toh batayein.\n\n{{business}}",
            ],
            // Quotation
            [
                'name'     => 'Quotation Sent',
                'category' => 'quotation',
                'body'     => "Hello {{name}} ji,\n\n📄 Aapka quotation taiyaar ho gaya hai!\n\nHumne aapke email {{email}} par bhej diya hai. Kripya check karein aur koi bhi changes ho toh batayein.\n\nDhanyavaad!\n{{business}}",
            ],
            [
                'name'     => 'Quotation Follow-up',
                'category' => 'followup',
                'body'     => "Hi {{name}},\n\nHumne aapko kuch din pehle ek quotation bheja tha.\n\nKya aapne review kiya? Koi sawaal ya changes chahiye ho toh please batayein. Hum aapki help karne ke liye ready hain! 🤝\n\n{{agent_name}}\n{{business}}",
            ],
            // Invoice
            [
                'name'     => 'Invoice Generated',
                'category' => 'invoice',
                'body'     => "Namaste {{name}} ji,\n\n🧾 Aapka invoice generate ho gaya hai.\n\nTotal Amount: ₹{{amount}}\nDue Date: {{date}}\n\nPayment ke liye apne email check karein ya humse contact karein.\n\nDhanyavaad!\n{{business}}",
            ],
            [
                'name'     => 'Payment Received',
                'category' => 'invoice',
                'body'     => "Hello {{name}} ji,\n\n✅ Aapka ₹{{amount}} ka payment successfully receive ho gaya hai!\n\nHumari services choose karne ke liye dhanyavaad. 🙏\n\nKoi bhi help chahiye ho toh hum yahan hain.\n\n{{business}}",
            ],
            // Promotion
            [
                'name'     => 'Special Offer',
                'category' => 'promotion',
                'body'     => "🎉 Namaste {{name}} ji!\n\n{{business}} ki taraf se ek khaas offer!\n\nSirf limited time ke liye yeh special discount available hai.\n\nAbhi contact karein aur benefit uthayein! 🚀\n\nOffer valid till: {{date}}\n\nDhanyavaad! 😊",
            ],
        ];

        foreach ($templates as $tpl) {
            WhatsappTemplate::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name'      => $tpl['name'],
                ],
                [
                    'tenant_id' => $tenantId,
                    'body'      => $tpl['body'],
                    'category'  => $tpl['category'],
                    'is_active' => true,
                ]
            );
        }
    }

    // ── Email Templates ───────────────────────────────────────────
    private function seedEmailTemplates(int $tenantId): void
    {
        $business = \App\Models\Tenant::find($tenantId)?->name ?? 'Our Company';

        $templates = [
            // General
            [
                'name'     => 'Welcome Email',
                'category' => 'general',
                'subject'  => 'Welcome to {{business}}! 🎉',
                'body'     => $this->welcomeEmailHtml(),
            ],
            // Follow-up
            [
                'name'     => 'Lead Follow-up Email',
                'category' => 'followup',
                'subject'  => 'Following up on your enquiry — {{business}}',
                'body'     => $this->followupEmailHtml(),
            ],
            // Reminder
            [
                'name'     => 'Meeting Reminder Email',
                'category' => 'reminder',
                'subject'  => 'Reminder: Your meeting on {{date}} — {{business}}',
                'body'     => $this->meetingReminderHtml(),
            ],
            [
                'name'     => 'Payment Reminder Email',
                'category' => 'reminder',
                'subject'  => 'Payment Reminder — ₹{{amount}} Due | {{business}}',
                'body'     => $this->paymentReminderHtml(),
            ],
            // Quotation
            [
                'name'     => 'Quotation Email',
                'category' => 'quotation',
                'subject'  => 'Quotation from {{business}} — Please Review',
                'body'     => $this->quotationEmailHtml(),
            ],
            // Invoice
            [
                'name'     => 'Invoice Email',
                'category' => 'invoice',
                'subject'  => 'Invoice from {{business}} — ₹{{amount}} Due on {{date}}',
                'body'     => $this->invoiceEmailHtml(),
            ],
            [
                'name'     => 'Payment Confirmation Email',
                'category' => 'invoice',
                'subject'  => 'Payment Received — Thank You! | {{business}}',
                'body'     => $this->paymentConfirmationHtml(),
            ],
            // Promotion
            [
                'name'     => 'Promotional Email',
                'category' => 'promotion',
                'subject'  => 'Special Offer Just for You! 🎉 | {{business}}',
                'body'     => $this->promotionEmailHtml(),
            ],
        ];

        foreach ($templates as $tpl) {
            EmailTemplate::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name'      => $tpl['name'],
                ],
                [
                    'tenant_id' => $tenantId,
                    'subject'   => $tpl['subject'],
                    'body'      => $tpl['body'],
                    'category'  => $tpl['category'],
                    'is_active' => true,
                ]
            );
        }
    }

    // ── HTML Templates ────────────────────────────────────────────

    private function baseHtml(string $title, string $content): string
    {
        return <<<HTML
<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;color:#1a1a2e;background:#ffffff">
  <div style="background:linear-gradient(135deg,#6378ff,#a78bfa);padding:32px 24px;text-align:center;border-radius:8px 8px 0 0">
    <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700">{{business}}</h1>
  </div>
  <div style="padding:32px 24px;background:#ffffff">
    {$content}
  </div>
  <div style="padding:20px 24px;background:#f8fafc;border-radius:0 0 8px 8px;text-align:center;border-top:1px solid #e2e8f0">
    <p style="margin:0;font-size:12px;color:#9ca3af">
      This email was sent by <strong>{{business}}</strong>.<br/>
      If you have any questions, reply to this email.
    </p>
  </div>
</div>
HTML;
    }

    private function welcomeEmailHtml(): string
    {
        $content = <<<CONTENT
<h2 style="color:#1a1a2e;margin:0 0 16px">Welcome, {{name}}! 🎉</h2>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">
  Thank you for choosing <strong>{{business}}</strong>. We're excited to have you with us!
</p>
<p style="color:#4b5563;line-height:1.7;margin:0 0 24px">
  Our team is ready to assist you. If you have any questions or need help, feel free to reach out to us at any time.
</p>
<div style="background:#f0f4ff;border-left:4px solid #6378ff;padding:16px;border-radius:4px;margin-bottom:24px">
  <p style="margin:0;color:#374151;font-size:14px">
    Your dedicated account manager is <strong>{{agent_name}}</strong>. Don't hesitate to contact them!
  </p>
</div>
<p style="color:#4b5563;line-height:1.7;margin:0">
  Warm regards,<br/>
  <strong>Team {{business}}</strong>
</p>
CONTENT;
        return $this->baseHtml('Welcome', $content);
    }

    private function followupEmailHtml(): string
    {
        $content = <<<CONTENT
<h2 style="color:#1a1a2e;margin:0 0 16px">Hi {{name}}, following up! 👋</h2>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">
  I hope this email finds you well! I'm reaching out to follow up on your recent enquiry with <strong>{{business}}</strong>.
</p>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">
  I wanted to check if you had any questions or if there's anything else we can help you with.
</p>
<div style="background:#f0f4ff;border-left:4px solid #6378ff;padding:16px;border-radius:4px;margin-bottom:24px">
  <p style="margin:0;color:#374151;font-size:14px">
    Please feel free to reply to this email or call us directly. We'd love to hear from you!
  </p>
</div>
<p style="color:#4b5563;line-height:1.7;margin:0">
  Best regards,<br/>
  <strong>{{agent_name}}</strong><br/>
  <span style="color:#9ca3af">{{business}}</span>
</p>
CONTENT;
        return $this->baseHtml('Follow-up', $content);
    }

    private function meetingReminderHtml(): string
    {
        $content = <<<CONTENT
<h2 style="color:#1a1a2e;margin:0 0 16px">Meeting Reminder ⏰</h2>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">Dear <strong>{{name}}</strong>,</p>
<p style="color:#4b5563;line-height:1.7;margin:0 0 24px">
  This is a friendly reminder about your upcoming meeting with <strong>{{business}}</strong>.
</p>
<div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:20px;margin-bottom:24px;text-align:center">
  <p style="margin:0 0 8px;font-size:14px;color:#92400e;font-weight:600">📅 Meeting Details</p>
  <p style="margin:0;font-size:18px;font-weight:700;color:#1a1a2e">{{date}}</p>
</div>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">
  Please make sure to be available at the scheduled time. If you need to reschedule, please let us know as soon as possible.
</p>
<p style="color:#4b5563;line-height:1.7;margin:0">
  See you soon!<br/>
  <strong>{{agent_name}}</strong><br/>
  <span style="color:#9ca3af">{{business}}</span>
</p>
CONTENT;
        return $this->baseHtml('Meeting Reminder', $content);
    }

    private function paymentReminderHtml(): string
    {
        $content = <<<CONTENT
<h2 style="color:#1a1a2e;margin:0 0 16px">Payment Reminder 💰</h2>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">Dear <strong>{{name}}</strong>,</p>
<p style="color:#4b5563;line-height:1.7;margin:0 0 24px">
  This is a friendly reminder that a payment is due for your account with <strong>{{business}}</strong>.
</p>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:20px;margin-bottom:24px;text-align:center">
  <p style="margin:0 0 4px;font-size:13px;color:#991b1b">Amount Due</p>
  <p style="margin:0 0 4px;font-size:28px;font-weight:800;color:#1a1a2e">₹{{amount}}</p>
  <p style="margin:0;font-size:13px;color:#991b1b">Due Date: {{date}}</p>
</div>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">
  Please make the payment at your earliest convenience to avoid any service interruption.
</p>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">
  If you have already made the payment, please disregard this email. For any queries, please contact us.
</p>
<p style="color:#4b5563;line-height:1.7;margin:0">
  Thank you,<br/>
  <strong>Team {{business}}</strong>
</p>
CONTENT;
        return $this->baseHtml('Payment Reminder', $content);
    }

    private function quotationEmailHtml(): string
    {
        $content = <<<CONTENT
<h2 style="color:#1a1a2e;margin:0 0 16px">Your Quotation is Ready! 📄</h2>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">Dear <strong>{{name}}</strong>,</p>
<p style="color:#4b5563;line-height:1.7;margin:0 0 24px">
  Thank you for your interest in <strong>{{business}}</strong>. Please find your quotation attached to this email.
</p>
<div style="background:#f0f4ff;border:1px solid #c7d2fe;border-radius:8px;padding:20px;margin-bottom:24px">
  <p style="margin:0 0 8px;font-size:13px;color:#4338ca;font-weight:600">📋 Quotation Details</p>
  <p style="margin:0 0 4px;font-size:13px;color:#374151">Total Amount: <strong>₹{{amount}}</strong></p>
  <p style="margin:0;font-size:13px;color:#374151">Valid Until: <strong>{{date}}</strong></p>
</div>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">
  Please review the quotation and let us know if you'd like to proceed or if you need any modifications.
</p>
<p style="color:#4b5563;line-height:1.7;margin:0">
  Best regards,<br/>
  <strong>{{agent_name}}</strong><br/>
  <span style="color:#9ca3af">{{business}}</span>
</p>
CONTENT;
        return $this->baseHtml('Quotation', $content);
    }

    private function invoiceEmailHtml(): string
    {
        $content = <<<CONTENT
<h2 style="color:#1a1a2e;margin:0 0 16px">Invoice from {{business}} 🧾</h2>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">Dear <strong>{{name}}</strong>,</p>
<p style="color:#4b5563;line-height:1.7;margin:0 0 24px">
  Please find your invoice details below.
</p>
<div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:24px">
  <div style="background:#f8fafc;padding:14px 20px;border-bottom:1px solid #e2e8f0">
    <p style="margin:0;font-size:13px;font-weight:600;color:#374151">Invoice Summary</p>
  </div>
  <div style="padding:16px 20px">
    <table style="width:100%;border-collapse:collapse">
      <tr>
        <td style="padding:8px 0;color:#6b7280;font-size:13px">Total Amount</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;color:#1a1a2e;font-size:16px">₹{{amount}}</td>
      </tr>
      <tr>
        <td style="padding:8px 0;color:#6b7280;font-size:13px;border-top:1px solid #f1f5f9">Due Date</td>
        <td style="padding:8px 0;text-align:right;font-weight:600;color:#1a1a2e;font-size:13px;border-top:1px solid #f1f5f9">{{date}}</td>
      </tr>
    </table>
  </div>
</div>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">
  Please make the payment by the due date. For any queries, feel free to contact us.
</p>
<p style="color:#4b5563;line-height:1.7;margin:0">
  Thank you for your business!<br/>
  <strong>Team {{business}}</strong>
</p>
CONTENT;
        return $this->baseHtml('Invoice', $content);
    }

    private function paymentConfirmationHtml(): string
    {
        $content = <<<CONTENT
<h2 style="color:#1a1a2e;margin:0 0 16px">Payment Received! ✅</h2>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">Dear <strong>{{name}}</strong>,</p>
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:20px;margin-bottom:24px;text-align:center">
  <p style="margin:0 0 4px;font-size:13px;color:#065f46;font-weight:600">✅ Payment Confirmed</p>
  <p style="margin:0 0 4px;font-size:28px;font-weight:800;color:#1a1a2e">₹{{amount}}</p>
  <p style="margin:0;font-size:13px;color:#065f46">Successfully received on {{date}}</p>
</div>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">
  Thank you for your payment. Your transaction has been completed successfully.
</p>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px">
  We appreciate your prompt payment and look forward to continuing our business relationship with you.
</p>
<p style="color:#4b5563;line-height:1.7;margin:0">
  Warm regards,<br/>
  <strong>Team {{business}}</strong>
</p>
CONTENT;
        return $this->baseHtml('Payment Confirmation', $content);
    }

    private function promotionEmailHtml(): string
    {
        $content = <<<CONTENT
<h2 style="color:#1a1a2e;margin:0 0 8px;text-align:center">🎉 Special Offer!</h2>
<p style="color:#6b7280;text-align:center;margin:0 0 24px;font-size:14px">Exclusive deal just for you, {{name}}!</p>
<div style="background:linear-gradient(135deg,#6378ff,#a78bfa);border-radius:12px;padding:28px;text-align:center;margin-bottom:24px">
  <p style="color:rgba(255,255,255,0.8);margin:0 0 8px;font-size:13px">Limited Time Offer</p>
  <p style="color:#ffffff;font-size:22px;font-weight:800;margin:0 0 8px;letter-spacing:-0.5px">
    Exclusive Discount Available!
  </p>
  <p style="color:rgba(255,255,255,0.85);margin:0;font-size:13px">Valid till {{date}}</p>
</div>
<p style="color:#4b5563;line-height:1.7;margin:0 0 16px;text-align:center">
  Don't miss out on this amazing opportunity from <strong>{{business}}</strong>!
</p>
<div style="text-align:center;margin-bottom:24px">
  <a href="#" style="display:inline-block;background:#6378ff;color:#ffffff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px">
    Claim Your Offer Now →
  </a>
</div>
<p style="color:#9ca3af;font-size:12px;text-align:center;margin:0">
  To unsubscribe, reply to this email with "Unsubscribe".
</p>
CONTENT;
        return $this->baseHtml('Promotion', $content);
    }
}
