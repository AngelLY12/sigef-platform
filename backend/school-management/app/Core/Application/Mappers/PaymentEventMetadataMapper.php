<?php

namespace App\Core\Application\Mappers;

use App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata\ChargeSucceededMetadataResponse;
use App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata\CheckoutSessionMetadataResponse;
use App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata\PaymentEventMetadataResponse;
use App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata\PaymentIntentCancelledMetadataResponse;
use App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata\PaymentIntentFailedMetadataResponse;
use App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata\PaymentIntentRequiresActionMetadataResponse;
use App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata\PaymentIntentSucceededMetadataResponse;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Charge\ChargeSucceededData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentCancelledData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentFailedData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentRequiresActionData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentSucceededData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\PaymentEventMetadata;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session\CheckoutSessionAsyncCompletedData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session\CheckoutSessionCompletedData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session\CheckoutSessionExpiredData;

class PaymentEventMetadataMapper
{
    public static function toResponse(
        PaymentEventType $paymentEventType,
        PaymentEventMetadata $metadata
    ): PaymentEventMetadataResponse {
        return match ($paymentEventType) {
            PaymentEventType::WEBHOOK_CHARGE_SUCCEEDED =>
            self::chargeSucceeded($metadata),

            PaymentEventType::WEBHOOK_PAYMENT_INTENT_SUCCEEDED =>
            self::paymentIntentSucceeded($metadata),

            PaymentEventType::WEBHOOK_PAYMENT_FAILED =>
            self::paymentIntentFailed($metadata),

            PaymentEventType::WEBHOOK_PAYMENT_CANCELLED =>
            self::paymentIntentCancelled($metadata),

            PaymentEventType::WEBHOOK_PAYMENT_REQUIRES_ACTION =>
            self::paymentIntentRequiresAction($metadata),

            PaymentEventType::WEBHOOK_SESSION_COMPLETED,
            PaymentEventType::WEBHOOK_SESSION_ASYNC_COMPLETED,
            PaymentEventType::WEBHOOK_SESSION_EXPIRED =>
            self::checkoutSession($metadata),
        };
    }

    private static function chargeSucceeded(
        PaymentEventMetadata $metadata
    ): ChargeSucceededMetadataResponse {
        if (!$metadata instanceof ChargeSucceededData) {
            throw new \LogicException('Invalid metadata for charge.succeeded.');
        }

        return ChargeSucceededMetadataResponse::create($metadata);
    }

    private static function paymentIntentSucceeded(
        PaymentEventMetadata $metadata
    ): PaymentIntentSucceededMetadataResponse {
        if (!$metadata instanceof PaymentIntentSucceededData) {
            throw new \LogicException(
                'Invalid metadata for payment_intent.succeeded.'
            );
        }

        return PaymentIntentSucceededMetadataResponse::create($metadata);
    }

    private static function paymentIntentFailed(
        PaymentEventMetadata $metadata
    ): PaymentIntentFailedMetadataResponse {
        if (!$metadata instanceof PaymentIntentFailedData) {
            throw new \LogicException(
                'Invalid metadata for payment_intent.payment_failed.'
            );
        }

        return PaymentIntentFailedMetadataResponse::create($metadata);
    }

    private static function paymentIntentCancelled(
        PaymentEventMetadata $metadata
    ): PaymentIntentCancelledMetadataResponse {
        if (!$metadata instanceof PaymentIntentCancelledData) {
            throw new \LogicException(
                'Invalid metadata for payment_intent.canceled.'
            );
        }

        return PaymentIntentCancelledMetadataResponse::create($metadata);
    }

    private static function paymentIntentRequiresAction(
        PaymentEventMetadata $metadata
    ): PaymentIntentRequiresActionMetadataResponse {
        if (!$metadata instanceof PaymentIntentRequiresActionData) {
            throw new \LogicException(
                'Invalid metadata for payment_intent.requires_action.'
            );
        }

        return PaymentIntentRequiresActionMetadataResponse::create($metadata);
    }

    private static function checkoutSession(
        PaymentEventMetadata $metadata
    ): CheckoutSessionMetadataResponse {
        if (
            !$metadata instanceof CheckoutSessionCompletedData
            && !$metadata instanceof CheckoutSessionAsyncCompletedData
            && !$metadata instanceof CheckoutSessionExpiredData
        ) {
            throw new \LogicException(
                'Invalid metadata for checkout session event.'
            );
        }

        return CheckoutSessionMetadataResponse::create($metadata);
    }
}
