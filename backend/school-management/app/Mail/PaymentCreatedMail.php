<?php

namespace App\Mail;

use App\Core\Application\DTO\Request\Mail\PaymentCreatedEmailDTO;
use App\Core\Domain\Utils\Helpers\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    protected PaymentCreatedEmailDTO $data;

    /**
     * Create a new message instance.
     */
    public function __construct(PaymentCreatedEmailDTO $data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de pago',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payments.created',
            with: [
                'recipientName' => $this->data->recipientName,
                'conceptName' => $this->data->concept_name,
                'amount' => Money::from($this->data->amount)->finalize(),
                'createdAt' => $this->data->created_at,
                'stripeSessionId' => $this->data->stripe_session_id,
                'url' => $this->data->url,
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
