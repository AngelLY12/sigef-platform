<?php

namespace App\Core\Domain\ValueObjects\EmailEvent;

final class ConceptCriticalAmountAlertEmailMetadata implements EmailEventMetadata
{
    public function __construct(
        public readonly string $emailTemplate,
        public readonly int    $conceptId,
        public readonly string $conceptName,
        public readonly string $fullName,
        public readonly string $amount,
        public readonly string $threshold,
        public readonly string $exceededBy,
        public readonly string $action,)
    {
    }

    public static function create(
        int    $conceptId,
        string $conceptName,
        string $fullName,
        string $amount,
        string $threshold,
        string $exceededBy,
        string $action): self
    {
        return new self(
            emailTemplate: 'concepts.critical-amount-alert',
            conceptId: $conceptId,
            conceptName: $conceptName,
            fullName: $fullName,
            amount: $amount,
            threshold: $threshold,
            exceededBy: $exceededBy,
            action: $action,
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            emailTemplate: $data['email_template'],
            conceptId: (int)$data['concept_id'],
            conceptName: $data['concept_name'],
            fullName: $data['full_name'],
            amount: $data['amount'],
            threshold: $data['threshold'],
            exceededBy: $data['exceeded_by'],
            action: $data['action'],
        );
    }

    public function toArray(): array
    {
        return [
            'email_template' => $this->emailTemplate,
            'concept_id' => $this->conceptId,
            'concept_name' => $this->conceptName,
            'full_name' => $this->fullName,
            'amount' => $this->amount,
            'threshold' => $this->threshold,
            'exceeded_by' => $this->exceededBy,
            'action' => $this->action,
        ];
    }

}
