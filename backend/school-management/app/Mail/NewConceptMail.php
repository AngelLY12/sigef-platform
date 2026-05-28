<?php

namespace App\Mail;

use App\Core\Application\DTO\Request\Mail\NewPaymentConceptEmailDTO;
use App\Core\Domain\Utils\Helpers\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewConceptMail extends Mailable
{
    use Queueable, SerializesModels;

    protected NewPaymentConceptEmailDTO $data;
    /**
     * Create a new message instance.
     */
    public function __construct(NewPaymentConceptEmailDTO $data)
    {
        $this->data = $data;

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo concepto de pago',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.concepts.new-concept',
            with: [
                'name'        => $this->data->recipientName,
                'conceptName' => $this->data->concept_name,
                'amount'      => Money::from($this->data->amount)->finalize(),
                'startDate'    => $this->data->start_date,
                'endDate'     => $this->data->end_date,
                'isDisable'    => $this->data->isDisable
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
