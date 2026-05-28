<?php

namespace App\Mail;

use App\Core\Application\DTO\Request\Mail\PaymentValidatedEmailDTO;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Utils\Helpers\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentValidatedMail extends Mailable
{
    use Queueable, SerializesModels;

    protected PaymentValidatedEmailDTO $data;

    /**
     * Create a new message instance.
     */
    public function __construct(PaymentValidatedEmailDTO $data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pago validado',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $isOxxo = ($this->data->payment_method_detail['type'] ?? '') === 'oxxo';
        $isSpei = ($this->data->payment_method_detail['type'] ?? '') === 'spei';
        return new Content(
            view: 'emails.payments.validated',
            with: [
                'recipientName' => $this->data->recipientName,
                'conceptName' => $this->data->concept_name,
                'amount' => $this->data->amount,
                'amountReceived' => $this->data->amount_received,
                'paymentMethodType' => $this->data->payment_method_detail['type'] ?? 'No especificado',
                'paymentIntentId' => $this->data->payment_intent_id,
                'reference' => $this->data->payment_method_detail['reference'],
                'url' => $this->data->url,
                'paymentLegend' => $this->buildPaymentLegend(),
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

    private function buildPaymentLegend(): string
    {
        $pending = Money::from((string) $this->data->amount)
            ->sub((string)$this->data->amount_received)
            ->finalize();
        $balance=Money::from((string) $this->data->amount_received)
            ->sub((string)$this->data->amount)
            ->finalize();
        return match ($this->data->status) {
            PaymentStatus::UNDERPAID->value =>
                "<p style='color:#d97706;'>
                Detectamos que el monto recibido es menor al esperado.
                <br>
                <strong>Monto pendiente:</strong> $"
                . $pending
                .
                "</p>",

            PaymentStatus::OVERPAID->value =>
                "<p style='color:#059669;'>
                Tu pago tiene un <strong>monto extra</strong>.
                <br>
                <strong>Saldo extra pagado:</strong> $"
                . $balance .
                "</p>",

            PaymentStatus::SUCCEEDED->value => '',

            default =>
            '',
        };
    }


}
