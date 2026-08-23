<?php

namespace App\Core\Domain\ValueObjects\EmailEvent;

use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\User;
use App\Core\Domain\ValueObjects\Payment\Stripe\RequiredActionDetails;

final class PaymentRequiresActionEmailMetadata implements EmailEventMetadata
{
    public function __construct(
        public readonly string $emailTemplate,
        public readonly int    $paymentId,
        public readonly int    $conceptId,
        public readonly string $conceptName,
        public readonly string $amount,
        public readonly string $recipientName,
        public readonly RequiredActionDetails $requiredActionDetails,
    )
    {
    }

    public static function create(
        Payment $payment,
        User    $user,
        RequiredActionDetails $requiredActionDetails): self
    {
        return new self(
            emailTemplate: 'payments.requires-action',
            paymentId: $payment->id,
            conceptId: $payment->payment_concept_id,
            conceptName: $payment->concept_name,
            amount: $payment->amount,
            recipientName: $user->fullName(),
            requiredActionDetails: $requiredActionDetails,
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            emailTemplate: $data['email_template'],
            paymentId: (int)$data['payment_id'],
            conceptId: (int)$data['concept_id'],
            conceptName: $data['concept_name'],
            amount: $data['amount'],
            recipientName: $data['recipientName'],
            requiredActionDetails: isset($data['required_action_details']) ? RequiredActionDetails::createFromArray($data['required_action_details']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'email_template' => $this->emailTemplate,
            'payment_id' => $this->paymentId,
            'concept_id' => $this->conceptId,
            'concept_name' => $this->conceptName,
            'amount' => $this->amount,
            'recipientName' => $this->recipientName,
            'required_action_details' => $this->requiredActionDetails?->toArray(),
        ];
    }

}
