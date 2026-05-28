<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Application\DTO\Response\General\ReconciliationResult;
use App\Core\Application\UseCases\Payments\Reconcile\BaseReconciliationUseCase;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Events\PaymentReconciledEvent;


class ReconcileSinglePaymentUseCase extends BaseReconciliationUseCase
{


    public function execute(string $eventId, string $sessionId): ReconciliationResult
    {
        $result = new ReconciliationResult();
        $alreadyCompleted = $this->paymentEventRep->findByStripeEvent(
            $eventId,
            PaymentEventType::RECONCILIATION_COMPLETED
        );

        if ($alreadyCompleted) {
            logger()->info("Evento ya procesado exitosamente: {$eventId}");
            return $result;
        }
        $payment = $this->paymentQueryRep->findBySessionId($sessionId);
        if(!$payment)
        {
            logger()->info("No se encontro el payment del evento: {$eventId}");
            return $result;
        }
        $result->processed = 1;

        try {
            [$pi, $charge] = $this->stripe->getIntentAndCharge($payment->payment_intent_id);

            $updatedPayment = $this->processReconciliation($payment, $pi, $charge);
            if ($updatedPayment) {
                $result->updated = 1;

                event(new PaymentReconciledEvent(
                    paymentId: $updatedPayment->id,
                    eventId: $eventId,
                    eventType: PaymentEventType::RECONCILIATION_COMPLETED->value,
                    sessionId: $sessionId,
                    outcome: 'success',
                    stripeData: [
                        'intent_status' => $pi->status,
                        'charge_id' => $charge->id,
                        'amount_received' => $charge->amount_received ?? null,
                    ],
                    previousStatus: $payment->status->value,
                    newStatus: $updatedPayment->status->value
                ));
                $this->handleSinglePaymentSideEffects($updatedPayment, $result);
            }
        } catch (\Throwable $e) {
            $result->failed = 1;

            event(new PaymentReconciledEvent(
                paymentId: $payment->id,
                eventId: $eventId,
                eventType: PaymentEventType::RECONCILIATION_FAILED->value,
                sessionId: $sessionId,
                outcome: 'error_payment_reconciliation',
                stripeData: null,
                newStatus: $payment->status->value,
                error: $e->getMessage()
            ));
            throw $e;
        }

        return $result;
    }

}
