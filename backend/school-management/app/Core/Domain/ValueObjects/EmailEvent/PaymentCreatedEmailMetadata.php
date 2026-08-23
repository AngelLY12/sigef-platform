<?php

namespace App\Core\Domain\ValueObjects\EmailEvent;

use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\User;

final class PaymentCreatedEmailMetadata implements EmailEventMetadata
{

    public function __construct(
        public readonly string $emailTemplate,
        public readonly string $recipientName,
        public readonly int $paymentId,
        public readonly string $conceptName,
        public readonly string $amount,
        public readonly ?string $createdAt,
        public readonly ?string $stripeSessionId,
        public readonly ?string $url, ) {}

    public static function create(Payment $payment, User $user): self
    {
        return new self(
            emailTemplate: 'payments.created',
            recipientName: $user->fullName(),
            paymentId: $payment->id,
            conceptName: $payment->concept_name,
            amount: $payment->amount,
            createdAt: $payment->created_at?->format('Y-m-d H:i:s'),
            stripeSessionId: $payment->stripe_session_id,
            url: $payment->url,
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            emailTemplate: $data['email_template'],
            recipientName: $data['recipientName'],
            paymentId: (int) $data['payment_id'],
            conceptName: $data['concept_name'],
            amount: $data['amount'],
            createdAt: $data['created_at'] ?? null,
            stripeSessionId: $data['stripe_session_id'] ?? null,
            url: $data['url'] ?? null,
        );
    }

    public function toArray(): array {
        return [
            'email_template' => $this->emailTemplate,
            'recipientName' => $this->recipientName,
            'payment_id' => $this->paymentId,
            'concept_name' => $this->conceptName,
            'amount' => $this->amount,
            'created_at' => $this->createdAt,
            'stripe_session_id' => $this->stripeSessionId,
            'url' => $this->url, ];
    }

}
