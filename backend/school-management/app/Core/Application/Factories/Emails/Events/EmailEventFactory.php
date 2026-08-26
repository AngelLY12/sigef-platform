<?php

namespace App\Core\Application\Factories\Emails\Events;

use App\Core\Application\DTO\Response\User\UserRecipientDTO;
use App\Core\Domain\Entities\EmailEvent;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\Enum\User\UserActorType;
use App\Core\Domain\Utils\Helpers\EventSourceId;
use App\Core\Domain\ValueObjects\EmailEvent\ConceptCreatedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\ConceptCriticalAmountAlertEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\ParentInvitedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\PaymentCreatedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\PaymentFailedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\PaymentRequiresActionEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\PaymentValidatedEmailMetadata;
use App\Core\Domain\ValueObjects\EmailEvent\UserCreatedEmailMetadata;
use App\Core\Domain\ValueObjects\Payment\Stripe\RequiredActionDetails;

final class EmailEventFactory
{
    public static function paymentCreated(
        Payment $payment,
        User $user,
        string $stripeEventId,
    ): EmailEvent {

        return EmailEvent::createEmailEvent(
            userId: $user->id,
            eventType: EmailEventType::PAYMENT_CREATED,
            recipientEmail: $user->email,
            sourceType:EmailEventSourceType::STRIPE,
            sourceId: $stripeEventId,
            metadata: PaymentCreatedEmailMetadata::create(payment: $payment, user: $user),
        );
    }

    public static function paymentValidated(
        Payment $payment,
        User $user,
        string $stripeEventId,
    ): EmailEvent {
        return EmailEvent::createEmailEvent(
            userId: $user->id,
            eventType: EmailEventType::PAYMENT_VALIDATED,
            recipientEmail: $user->email,
            sourceType: EmailEventSourceType::STRIPE,
            sourceId: $stripeEventId,
            metadata: PaymentValidatedEmailMetadata::create(payment: $payment, user: $user),
        );
    }

    public static function paymentFailed(
        Payment $payment,
        User $user,
        string $stripeEventId,
        string $errorMessage,
    ): EmailEvent {

        return EmailEvent::createEmailEvent(
            userId: $user->id,
            eventType: EmailEventType::PAYMENT_FAILED,
            recipientEmail: $user->email,
            sourceType: EmailEventSourceType::STRIPE,
            sourceId: $stripeEventId,
            metadata: PaymentFailedEmailMetadata::create(payment: $payment, user: $user, errorMessage: $errorMessage),
        );
    }

    public static function paymentRequiresAction(
        Payment $payment,
        User $user,
        string $stripeEventId,
        RequiredActionDetails $requiredAction
    ): EmailEvent {

        return EmailEvent::createEmailEvent(
            userId: $user->id,
            eventType: EmailEventType::PAYMENT_REQUIRES_ACTION,
            recipientEmail: $user->email,
            sourceType: EmailEventSourceType::STRIPE,
            sourceId: $stripeEventId,
            metadata: PaymentRequiresActionEmailMetadata::create(
                payment: $payment,
                user: $user,
                requiredActionDetails: $requiredAction,
            ),
        );
    }

    public static function userCreated(
        User $user,
        UserActorType $actorType,
        string $operationId,
    ): EmailEvent {
        $eventType = EmailEventType::USER_CREATED;
        $sourceType = EmailEventSourceType::USER;

        return EmailEvent::createEmailEvent(
            userId: $user->id,
            eventType: $eventType,
            recipientEmail: $user->email,
            sourceType: $sourceType,
            sourceId: EventSourceId::email(
                $sourceType,
                $eventType,
                $operationId,
                $user->id
            ),
            metadata: UserCreatedEmailMetadata::create(user: $user, actorType: $actorType),
        );
    }

    public static function parentInvited(
        User $user,
        string $operationId,
    ): EmailEvent {
        $eventType = EmailEventType::PARENT_INVITED;
        $sourceType = EmailEventSourceType::USER;
        return EmailEvent::createEmailEvent(
            userId: $user->id,
            eventType: $eventType,
            recipientEmail: $user->email,
            sourceType: $sourceType,
            sourceId: EventSourceId::email(
                sourceType: $sourceType,
                eventType: $eventType,
                operationId: $operationId,
                recipientId: $user->id
            ),
            metadata: ParentInvitedEmailMetadata::create(user: $user),
        );
    }

    public static function conceptCreated(
        UserRecipientDTO $user,
        PaymentConcept $concept,
        string $operationId,
    ): EmailEvent {
        $eventType = EmailEventType::CONCEPT_CREATED;
        $sourceType = EmailEventSourceType::CONCEPT;

        return EmailEvent::createEmailEvent(
            userId: $user->id,
            eventType: $eventType,
            recipientEmail: $user->email,
            sourceType: $sourceType,
            sourceId: EventSourceId::email(
                $sourceType,
                $eventType,
                $operationId,
                $user->id
            ),
            metadata: ConceptCreatedEmailMetadata::create(user: $user, concept: $concept),
        );
    }

    public static function conceptCriticalAmountAlert(
        int $userId,
        int $conceptId,
        string $conceptName,
        string $recipientEmail,
        string $fullName,
        string $amount,
        string $threshold,
        string $exceededBy,
        string $action,
        string $operationId,
    ): EmailEvent {
        $eventType = EmailEventType::CONCEPT_CRITICAL_AMOUNT_ALERT;
        $sourceType = EmailEventSourceType::SYSTEM;

        return EmailEvent::createEmailEvent(
            userId: $userId,
            eventType: $eventType,
            recipientEmail: $recipientEmail,
            sourceType: $sourceType,
            sourceId: EventSourceId::email(
                $sourceType,
                $eventType,
                $operationId,
                $userId
            ),
            metadata: ConceptCriticalAmountAlertEmailMetadata::create(
                conceptId: $conceptId,
                conceptName: $conceptName,
                fullName: $fullName,
                amount: $amount,
                threshold: $threshold,
                exceededBy: $exceededBy,
                action: $action,
            ),
        );
    }

}
