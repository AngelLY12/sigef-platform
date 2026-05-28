<?php

namespace App\Mail;

use App\Core\Application\DTO\Request\Mail\NewUserCreatedEmailDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreatedUserMail extends Mailable
{
    use Queueable, SerializesModels;

    protected NewUserCreatedEmailDTO $data;

    /**
     * Create a new message instance.
     */
    public function __construct(NewUserCreatedEmailDTO $data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cuenta creada',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.users.created',
            with: [
                'name' => $this->data->recipientName,
                'password' => $this->data->password,
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
