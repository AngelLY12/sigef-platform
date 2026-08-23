<?php

namespace App\Core\Application\Mappers;

use App\Core\Application\DTO\Response\Events\EmailEvent\Metadata\ConceptCreatedMetadataResponse;
use App\Core\Application\DTO\Response\Events\EmailEvent\Metadata\ConceptCriticalAmountAlertMetadataResponse;
use App\Core\Application\DTO\Response\Events\EmailEvent\Metadata\EmailEventMetadataResponse;
use App\Core\Application\DTO\Response\Events\EmailEvent\Metadata\PaymentCreatedMetadataResponse;
use App\Core\Application\DTO\Response\Events\EmailEvent\Metadata\PaymentFailedMetadataResponse;
use App\Core\Application\DTO\Response\Events\EmailEvent\Metadata\PaymentRequiresActionMetadataResponse;
use App\Core\Application\DTO\Response\Events\EmailEvent\Metadata\PaymentValidatedMetadataResponse;
use App\Core\Application\DTO\Response\Events\EmailEvent\Metadata\UserCreatedMetadataResponse;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\ValueObjects\EmailEvent\ConceptCreatedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\ConceptCriticalAmountAlertEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\EmailEventMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\PaymentCreatedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\PaymentFailedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\PaymentRequiresActionEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\PaymentValidatedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\UserCreatedEmailMetadata;

class EmailEventMetadataMapper
{

    public static function toResponse(
        EmailEventType $eventType,
        EmailEventMetadata $eventMetadata
    ): EmailEventMetadataResponse
    {
        return match ($eventType) {
            EmailEventType::CONCEPT_CREATED =>
                self::conceptCreated($eventMetadata),
            EmailEventType::CONCEPT_CRITICAL_AMOUNT_ALERT =>
                self::conceptCriticalAmountAlert($eventMetadata),
            EmailEventType::PAYMENT_CREATED =>
                self::paymentCreated($eventMetadata),
            EmailEventType::PAYMENT_FAILED =>
                self::paymentFailed($eventMetadata),
            EmailEventType::PAYMENT_REQUIRES_ACTION =>
                self::paymentRequiresAction($eventMetadata),
            EmailEventType::PAYMENT_VALIDATED =>
                self::paymentValidated($eventMetadata),
            EmailEventType::USER_CREATED =>
                self::userCreated($eventMetadata),
        };
    }

    private static function conceptCreated(
        EmailEventMetadata $metadata
    ): ConceptCreatedMetadataResponse {
        if (!$metadata instanceof ConceptCreatedEmailMetadata) {
            throw new \LogicException('Invalid metadata for concept_created.');
        }

        return ConceptCreatedMetadataResponse::create($metadata);
    }

    private static function conceptCriticalAmountAlert(
        EmailEventMetadata $metadata
    ): ConceptCriticalAmountAlertMetadataResponse {
        if(!$metadata instanceof ConceptCriticalAmountAlertEmailMetadata)
        {
            throw new \LogicException('Invalid metadata for concept_critical_amount_alert.');
        }
        return ConceptCriticalAmountAlertMetadataResponse::create($metadata);
    }

    private static function paymentCreated(
        EmailEventMetadata $metadata
    ): PaymentCreatedMetadataResponse {
        if(!$metadata instanceof PaymentCreatedEmailMetadata)
        {
            throw new \LogicException('Invalid metadata for payment_created.');
        }
        return PaymentCreatedMetadataResponse::create($metadata);
    }

    private static function paymentFailed(
        EmailEventMetadata $metadata
    ): PaymentFailedMetadataResponse {
        if(!$metadata instanceof PaymentFailedEmailMetadata)
        {
            throw new \LogicException('Invalid metadata for payment_failed.');
        }
        return PaymentFailedMetadataResponse::create($metadata);
    }

    private static function paymentRequiresAction(
        EmailEventMetadata $metadata
    ): PaymentRequiresActionMetadataResponse
    {
        if(!$metadata instanceof PaymentRequiresActionEmailMetadata)
        {
            throw new \LogicException('Invalid metadata for payment_requires_action.');
        }
        return PaymentRequiresActionMetadataResponse::create($metadata);
    }

    private static function paymentValidated(
        EmailEventMetadata $metadata
    ): PaymentValidatedMetadataResponse
    {
        if(!$metadata instanceof PaymentValidatedEmailMetadata)
        {
            throw new \LogicException('Invalid metadata for payment_validated.');
        }
        return PaymentValidatedMetadataResponse::create($metadata);
    }

    private static function userCreated(
        EmailEventMetadata $metadata
    ): UserCreatedMetadataResponse
    {
        if(!$metadata instanceof UserCreatedEmailMetadata)
        {
            throw new \LogicException('Invalid metadata for user_created.');
        }
        return UserCreatedMetadataResponse::create($metadata);
    }
}
