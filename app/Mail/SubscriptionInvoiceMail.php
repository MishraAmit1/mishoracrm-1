<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array  $inv       View-model from SubscriptionInvoiceService::invoiceData()
     * @param string $pdfBytes  Rendered PDF content
     * @param string $filename  Attachment filename
     */
    public function __construct(
        public array $inv,
        public string $pdfBytes,
        public string $filename,
    ) {}

    public function envelope(): Envelope
    {
        $seller = $this->inv['seller']['name'];

        return new Envelope(
            subject: "Tax Invoice {$this->inv['invoice_number']} — {$this->inv['line']['title']} · {$seller}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscription-invoice');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBytes, $this->filename)
                ->withMime('application/pdf'),
        ];
    }
}
