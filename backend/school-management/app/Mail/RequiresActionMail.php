<?php

namespace App\Mail;

use App\Core\Application\DTO\Request\Mail\RequiresActionEmailDTO;
use App\Core\Domain\Utils\Helpers\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequiresActionMail extends Mailable
{
    use Queueable, SerializesModels;

    protected RequiresActionEmailDTO $data;

    public function __construct(RequiresActionEmailDTO $data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Acción requerida para completar tu pago',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $amount = Money::from((string) $this->data->amount)
            ->divide('100')
            ->finalize();

        if ($this->data->next_action['type'] === 'oxxo') {
            return new Content(
                view: 'emails.payments.requires-action',
                with: [
                    'recipientName'  => $this->data->recipientName,
                    'headerTitle'    => 'Instrucciones para completar tu pago en OXXO',
                    'messageIntro'   => 'Para completar tu pago, acude a cualquier tienda OXXO y presenta el código de referencia en el voucher:',
                    'amount'         => $amount,
                    'paymentMethod'  => 'oxxo',
                    'reference'      => $this->data->next_action['reference'],
                    'url'            => $this->data->next_action['url'],
                    'expirationDays' => $this->data->payment_method_options['expires_after_days'] ?? null,
                ]
            );
        }

        return new Content(
            view: 'emails.payments.requires-action',
            with: [
                'recipientName' => $this->data->recipientName,
                'headerTitle'   => 'Instrucciones para completar tu pago por transferencia bancaria',
                'messageIntro'  => 'Para completar tu pago, realiza una transferencia bancaria utilizando los siguientes datos:',
                'amount'        => $amount,
                'paymentMethod' => 'bank_transfer',
                'reference'     => $this->data->next_action['reference'],
                'url'           => $this->data->next_action['url'],
                'expirationDays' => null,
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
