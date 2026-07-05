<?php

namespace App\Mail;

use App\Models\BatchUnit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductInfoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BatchUnit $unit,
        public string $recipientName
    ) {}

    public function envelope(): Envelope
    {
        $product = $this->unit->batch?->product?->name ?? 'Your Product';
        return new Envelope(subject: "Genuine Product Confirmed — {$product}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.product-info', with: [
            'unit'    => $this->unit,
            'name'    => $this->recipientName,
            'product' => $this->unit->batch?->product,
            'batch'   => $this->unit->batch,
        ]);
    }
}
