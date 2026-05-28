<?php

namespace App\Mail;

use App\Core\Application\DTO\Request\Mail\PaymentFailedEmailDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    protected PaymentFailedEmailDTO $data;


    /**
     * Create a new message instance.
     */
    public function __construct(PaymentFailedEmailDTO $data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Error al procesar el pago',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payments.failed',
            with: [
                'recipientName' => $this->data->recipientName,
                'conceptName' => $this->data->concept_name,
                'amount' => $this->data->amount,
                'error' => $this->data->error,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

}
