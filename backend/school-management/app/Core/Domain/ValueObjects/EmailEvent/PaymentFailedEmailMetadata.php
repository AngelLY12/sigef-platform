<?php

namespace App\Core\Domain\ValueObjects\EmailEvent;

use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\User;

final class PaymentFailedEmailMetadata implements EmailEventMetadata
{
    public function __construct(
        public readonly string $emailTemplate,
        public readonly string $recipientName,
        public readonly string $error,
        public readonly int    $paymentId,
        public readonly int    $conceptId,
        public readonly string $conceptName,
        public readonly string $amount,
        public readonly string $amountReceived
    )
    {
    }

    public static function create(Payment $payment, User $user, string $errorMessage): self
    {
        return new self(
            emailTemplate: 'payments.failed',
            recipientName: $user->fullName(),
            error: $errorMessage,
            paymentId: $payment->id,
            conceptId: $payment->payment_concept_id,
            conceptName: $payment->concept_name,
            amount: $payment->amount,
            amountReceived: $payment->amount_received,
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            emailTemplate: $data['email_template'],
            recipientName: $data['recipientName'],
            error: $data['error'],
            paymentId: (int)$data['payment_id'],
            conceptId: (int)$data['concept_id'],
            conceptName: $data['concept_name'],
            amount: $data['amount'],
            amountReceived: $data['amount_received'],
        );
    }

    public function toArray(): array
    {
        return [
            'email_template' => $this->emailTemplate,
            'recipientName' => $this->recipientName,
            'error' => $this->error,
            'payment_id' => $this->paymentId,
            'concept_id' => $this->conceptId,
            'concept_name' => $this->conceptName,
            'amount' => $this->amount,
            'amount_received' => $this->amountReceived,
        ];
    }

}
