<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * One summary email for every audience: the customer's copy with the
 * manage link, the store owner's heads-up copy without it. Heading
 * and intro come from the caller.
 */
class OrderSummaryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $heading,
        public readonly string $intro,
        public readonly bool $forCustomer = true,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->heading,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.order-summary',
            with: [
                'order' => $this->order,
                'heading' => $this->heading,
                'intro' => $this->intro,
                'forCustomer' => $this->forCustomer,
            ],
        );
    }
}
