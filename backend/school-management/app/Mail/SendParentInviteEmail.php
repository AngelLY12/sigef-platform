<?php

namespace App\Mail;

use App\Core\Application\DTO\Request\Mail\SendParentInviteEmailDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendParentInviteEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    protected SendParentInviteEmailDTO $data;

    public function __construct(SendParentInviteEmailDTO $data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación de vinculación',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.parents.invite',
            with: [
                'recipientName' => $this->data->recipientName,
                'acceptUrl' => config('app.frontend_url')
                    . '/parent/accept-invite?token='
                    . $this->data->token,
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
