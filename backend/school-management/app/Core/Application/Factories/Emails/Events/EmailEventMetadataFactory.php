<?php

namespace App\Core\Application\Factories\Emails\Events;

use App\Core\Domain\ValueObjects\EmailEvent\ConceptCreatedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\ConceptCriticalAmountAlertEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\EmailEventMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\PaymentCreatedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\PaymentFailedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\PaymentRequiresActionEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\PaymentValidatedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\UserCreatedEmailMetadata;

final class EmailEventMetadataFactory
{

    public static function fromArray(array $data): EmailEventMetadata
    {
        return match ($data['email_template'] ?? null) {
            'payments.created' =>
            PaymentCreatedEmailMetadata::createFromArray($data),

            'payments.validated' =>
            PaymentValidatedEmailMetadata::createFromArray($data),

            'payments.failed' =>
            PaymentFailedEmailMetadata::createFromArray($data),

            'payments.requires-action' =>
            PaymentRequiresActionEmailMetadata::createFromArray($data),

            'users.created' =>
            UserCreatedEmailMetadata::createFromArray($data),

            'concepts.new-concept' =>
            ConceptCreatedEmailMetadata::createFromArray($data),

            'concepts.critical-amount-alert' =>
            ConceptCriticalAmountAlertEmailMetadata::createFromArray($data),
            default => throw new \InvalidArgumentException(
                "Tipo de metadata de EmailEvent no soportado: " .
                ($data['email_template'] ?? 'null')
            ),
        };
    }

}
