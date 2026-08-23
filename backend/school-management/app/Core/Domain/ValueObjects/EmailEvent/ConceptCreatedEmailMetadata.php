<?php

namespace App\Core\Domain\ValueObjects\EmailEvent;

use App\Core\Application\DTO\Response\User\UserRecipientDTO;
use App\Core\Domain\Entities\PaymentConcept;

final class ConceptCreatedEmailMetadata implements EmailEventMetadata
{
    public function __construct(
        public readonly string $emailTemplate,
        public readonly string $recipientName,
        public readonly string $conceptName,
        public readonly string $amount,
        public readonly ?string $endDate,
        public readonly string $startDate,
        public readonly bool   $isDisable,
    )
    {
    }

    public static function create(UserRecipientDTO $user, PaymentConcept $concept): self
    {
        return new self(
            emailTemplate: 'concepts.new-concept',
            recipientName: $user->fullName,
            conceptName: $concept->concept_name,
            amount: $concept->amount,
            endDate: $concept->end_date ?? null,
            startDate: $concept->start_date,
            isDisable: $concept->isDisable()
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            emailTemplate: $data['email_template'],
            recipientName: $data['recipientName'],
            conceptName: $data['concept_name'],
            amount: $data['amount'],
            endDate: $data['end_date'] ?? null,
            startDate: $data['start_date'],
            isDisable: (bool)$data['isDisable']
        );
    }

    public function toArray(): array
    {
        return [
            'email_template' => $this->emailTemplate,
            'recipientName' => $this->recipientName,
            'concept_name' => $this->conceptName,
            'amount' => $this->amount,
            'end_date' => $this->endDate,
            'start_date' => $this->startDate,
            'isDisable' => $this->isDisable,
        ];
    }

}
