<?php

namespace App\Core\Application\Services\Payments\Stripe;

use App\Core\Application\UseCases\Payments\Stripe\DetachPaymentMethodUseCase;
use App\Core\Application\UseCases\Payments\Stripe\HandleFailedOrExpiredPaymentUseCase;
use App\Core\Application\UseCases\Payments\Stripe\PaymentMethodAttachedUseCase;
use App\Core\Application\UseCases\Payments\Stripe\ReconcileSinglePaymentUseCase;
use App\Core\Application\UseCases\Payments\Stripe\RequiresActionUseCase;
use App\Core\Application\UseCases\Payments\Stripe\SessionAsyncCompletedUseCase;
use App\Core\Application\UseCases\Payments\Stripe\SessionCompletedUseCase;
use App\Core\Application\UseCases\Payments\Stripe\UpdatePaymentMethodUseCase;
use App\Core\Domain\Entities\Payment;

class WebhookServiceFacades{

    public function __construct(
       private SessionCompletedUseCase $session,
       private SessionAsyncCompletedUseCase $async,
       private PaymentMethodAttachedUseCase $attached,
       private RequiresActionUseCase $requires,
       private HandleFailedOrExpiredPaymentUseCase $handle,
       private ReconcileSinglePaymentUseCase $reconcile,
        private UpdatePaymentMethodUseCase $update,
        private DetachPaymentMethodUseCase $detachPm
    ) {

    }

    public function sessionCompleted($obj, string $eventId)
    {
        return $this->session->execute($obj, $eventId);
    }

    public function sessionAsync($obj, string $eventId) {
        return $this->async->execute($obj, $eventId);
    }

    public function paymentMethodAttached($obj, string $eventId){

       return $this->attached->execute($obj, $eventId);
    }

    public function requiresAction($obj, string $eventId){
        return $this->requires->execute($obj, $eventId);
    }

    public function handleFailedOrExpiredPayment($obj, string $eventType, string $eventId)
    {
        return $this->handle->execute($obj,$eventType, $eventId);

    }

    public function reconcilePayment(string $eventId, string $sessionId)
    {
        return $this->reconcile->execute($eventId, $sessionId);
    }

    public function updatePaymentMethodAutomatically($obj, string $eventType, string $eventId): bool
    {
        return $this->update->execute($obj, $eventType, $eventId);
    }

    public function detachPaymentMethod($obj, string $eventType, string $eventId): bool
    {
        return $this->detachPm->execute($obj, $eventType, $eventId);
    }
}
