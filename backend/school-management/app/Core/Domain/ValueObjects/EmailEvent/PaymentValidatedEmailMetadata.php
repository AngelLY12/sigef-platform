<?php

namespace App\Core\Domain\ValueObjects\EmailEvent;

use App\Core\Application\Factories\Payments\Stripe\StripePaymentMethodDetailsFactory;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\User;
use App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails\PaymentMethodDetails;

final class PaymentValidatedEmailMetadata implements EmailEventMetadata
{
    public function __construct(
        public readonly string                $emailTemplate,
        public readonly string                $recipientName,
        public readonly int                   $paymentId,
        public readonly string                $conceptName,
        public readonly string                $amount,
        public readonly string                $amountReceived,
        public readonly ?PaymentMethodDetails $paymentMethodDetail,
        public readonly string                $status,
        public readonly ?string               $paymentIntentId,
        public readonly ?string               $url)
    {
    }

    public static function create(Payment $payment, User $user): self
    {
        return new self(
            emailTemplate: 'payments.validated',
            recipientName: $user->fullName(),
            paymentId: $payment->id,
            conceptName: $payment->concept_name,
            amount: $payment->amount,
            amountReceived: $payment->amount_received,
            paymentMethodDetail: $payment->payment_method_details,
            status: $payment->status->value,
            paymentIntentId: $payment->payment_intent_id,
            url: $payment->url,
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            emailTemplate: $data['email_template'],
            recipientName: $data['recipientName'],
            paymentId: (int)$data['payment_id'],
            conceptName: $data['concept_name'],
            amount: $data['amount'],
            amountReceived: $data['amount_received'],
            paymentMethodDetail: isset($data['payment_method_detail'])
                ? StripePaymentMethodDetailsFactory::fromArray($data['payment_method_detail']) : null,
            status: $data['status'],
            paymentIntentId: $data['payment_intent_id'] ?? null,
            url: $data['url'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'email_template' => $this->emailTemplate,
            'recipientName' => $this->recipientName,
            'payment_id' => $this->paymentId,
            'concept_name' => $this->conceptName,
            'amount' => $this->amount,
            'amount_received' => $this->amountReceived,
            'payment_method_detail' => $this->paymentMethodDetail?->toArray(),
            'status' => $this->status,
            'payment_intent_id' => $this->paymentIntentId,
            'url' => $this->url,
        ];
    }

}
