<?php

namespace App\Core\Application\Services\Facades\Payments\Stripe;

use App\Core\Application\UseCases\Payments\Stripe\ChargeSucceededUseCase;
use App\Core\Application\UseCases\Payments\Stripe\DetachPaymentMethodUseCase;
use App\Core\Application\UseCases\Payments\Stripe\HandleFailedOrExpiredPaymentUseCase;
use App\Core\Application\UseCases\Payments\Stripe\PaymentIntentSucceededUseCase;
use App\Core\Application\UseCases\Payments\Stripe\PaymentMethodAttachedUseCase;
use App\Core\Application\UseCases\Payments\Stripe\RequiresActionUseCase;
use App\Core\Application\UseCases\Payments\Stripe\SessionAsyncCompletedUseCase;
use App\Core\Application\UseCases\Payments\Stripe\SessionCompletedUseCase;
use App\Core\Application\UseCases\Payments\Stripe\UpdatePaymentMethodUseCase;
use App\Core\Domain\Entities\Payment;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;

class WebhookServiceFacades{

    public function __construct(
       private SessionCompletedUseCase $session,
       private SessionAsyncCompletedUseCase $async,
       private PaymentMethodAttachedUseCase $attached,
       private RequiresActionUseCase $requires,
       private HandleFailedOrExpiredPaymentUseCase $handle,
        private UpdatePaymentMethodUseCase $update,
        private DetachPaymentMethodUseCase $detachPm,
        private ChargeSucceededUseCase $chargeSucceeded,
        private PaymentIntentSucceededUseCase $paymentIntentSucceeded,
    ) {

    }

    public function sessionCompleted(Session $obj, string $eventId): bool
    {
        return $this->session->execute($obj, $eventId);
    }

    public function chargeSucceeded(Charge $obj, string $eventId): Payment
    {
        return $this->chargeSucceeded->execute($eventId, $obj);
    }

    public function paymentIntentSucceeded(PaymentIntent $obj, string $eventId): bool
    {
        return $this->paymentIntentSucceeded->execute($obj, $eventId);
    }

    public function sessionAsync(Session $obj, string $eventId): bool
    {
        return $this->async->execute($obj, $eventId);
    }

    public function paymentMethodAttached(PaymentMethod $obj, string $eventId): bool
    {

       return $this->attached->execute($obj, $eventId);
    }

    public function requiresAction($obj, string $eventId): bool
    {
        return $this->requires->execute($obj, $eventId);
    }

    public function handleFailedOrExpiredPayment(Session|PaymentIntent $obj, string $eventType, string $eventId): bool
    {
        return $this->handle->execute($obj,$eventType, $eventId);

    }

    public function updatePaymentMethodAutomatically(PaymentMethod $obj, string $eventType, string $eventId): bool
    {
        return $this->update->execute($obj, $eventType, $eventId);
    }

    public function detachPaymentMethod(PaymentMethod $obj, string $eventType, string $eventId): bool
    {
        return $this->detachPm->execute($obj, $eventType, $eventId);
    }
}
