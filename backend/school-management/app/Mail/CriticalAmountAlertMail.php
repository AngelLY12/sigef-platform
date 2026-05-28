<?php

namespace App\Mail;

use App\Core\Domain\Utils\Helpers\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CriticalAmountAlertMail extends Mailable
{
    use Queueable, SerializesModels;
    public string $amount;
    public int $id;
    public string $email;
    public string $concept_name;
    public string $fullName;
    public string $threshold;
    public string $exceededBy;
    public string $action;
    /**
     * Create a new message instance.
     */
    public function __construct(string $amount,
                                int $id,
                                string $concept_name,
                                string $fullName,
                                string $email,
                                string $threshold,
                                string $exceededBy,
    string $action)
    {
        $this->amount = $amount;
        $this->id = $id;
        $this->concept_name = $concept_name;
        $this->fullName = $fullName;
        $this->email = $email;
        $this->threshold = $threshold;
        $this->exceededBy = $exceededBy;
        $this->action = $action;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('concepts.amount.notifications.mail.title')
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.concepts.critical-amount-alert',
            with: [
                'title'        => config('concepts.amount.notifications.mail.title'),
                'intro'        => config('concepts.amount.notifications.mail.intro'),
                'fullName'     => $this->fullName,
                'conceptId'    => $this->id,
                'conceptName'  => $this->concept_name,
                'amount'       => Money::from($this->amount)->finalize(),
                'threshold'    => $this->threshold,
                'exceededBy'   => $this->exceededBy,
                'action'       => $this->action,
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
