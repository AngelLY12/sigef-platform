<?php

namespace App\Core\Application\UseCases\Payments\Reconcile;

use App\Core\Application\DTO\Response\General\ReconciliationResult;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Events\PaymentReconciledEvent;

class ReconcilePaymentsForceUseCase extends BaseReconciliationUseCase
{

    public function execute(Payment $payment): array
    {
        $result = new ReconciliationResult();
        $alreadyCompleted = $this->paymentEventRep->existsByPaymentId(
            $payment->id,
            PaymentEventType::RECONCILIATION_COMPLETED
        );

        if ($alreadyCompleted) {
            logger()->info("Evento ya procesado exitosamente para el payment: {$payment->id}");
            return [$result, $payment, false];
        }

        $result->processed = 1;

        try {
            [$pi, $charge] = $this->stripe->getIntentAndCharge($payment->payment_intent_id);

            $updatedPayment = $this->processReconciliation($payment, $pi, $charge);
            if ($updatedPayment) {
                $result->updated = 1;

                event(new PaymentReconciledEvent(
                    paymentId: $updatedPayment->id,
                    eventId: null,
                    eventType: PaymentEventType::RECONCILIATION_COMPLETED->value,
                    sessionId: null,
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
                eventId: null,
                eventType: PaymentEventType::RECONCILIATION_FAILED->value,
                sessionId: null,
                outcome: 'error_payment_reconciliation',
                stripeData: null,
                newStatus: $payment->status->value,
                error: $e->getMessage()
            ));
            throw $e;
        }

        return [$result, $updatedPayment, true];
    }

}
