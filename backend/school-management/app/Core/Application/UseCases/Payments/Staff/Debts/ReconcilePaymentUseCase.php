<?php

namespace App\Core\Application\UseCases\Payments\Staff\Debts;

use App\Core\Application\DTO\Response\Payment\PaymentReconciliationResponse;
use App\Core\Application\Services\Reconciliation\PaymentReconciliationServiceInterface;
use App\Core\Domain\Enum\Events\Status\ReconciliationEventStatus;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Repositories\Query\Events\PaymentEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\Events\ReconciliationEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Utils\Helpers\EventSourceId;
use App\Core\Domain\Utils\Validators\PaymentValidator;
use App\Exceptions\Conflict\ReconciliationAlreadyCompleted;
use App\Exceptions\NotFound\PaymentNotFountException;

class ReconcilePaymentUseCase
{
    private const PAYMENT_EVENTS = [PaymentEventType::WEBHOOK_CHARGE_SUCCEEDED, PaymentEventType::WEBHOOK_PAYMENT_INTENT_SUCCEEDED, PaymentEventType::WEBHOOK_SESSION_COMPLETED];
    public function __construct(
        private PaymentQueryRepInterface $paymentQueryRep,
        private PaymentEventQueryRepInterface $paymentEventQueryRep,
        private PaymentReconciliationServiceInterface $paymentReconciliationService,
        private ReconciliationEventQueryRepInterface $reconciliationEventQueryRep
    ){}

    public function execute(int $paymentId, int $userId): PaymentReconciliationResponse
    {
        $payment = $this->paymentQueryRep->findByIdAndUserId(paymentId: $paymentId, userId: $userId);
        if(!$payment){
            throw new PaymentNotFountException('No se encontro el pago para este usuario');
        }
        $reconciliationEvent= $this->reconciliationEventQueryRep->findByPaymentIdAndEventTypeAndStatus(paymentId: $payment->id, status: ReconciliationEventStatus::COMPLETED);
        if($reconciliationEvent){
            throw new ReconciliationAlreadyCompleted();
        }

        PaymentValidator::ensurePaymentIsValidToReconcile($payment);
        $paymentEvents = $this->paymentEventQueryRep->findByPaymentAndEventTypes(paymentId: $payment->id, eventTypes: self::PAYMENT_EVENTS);
        $operationId= EventSourceId::generateOperationId();
        return $this->paymentReconciliationService->reconcile(payment: $payment, events: $paymentEvents, operationId: $operationId);
    }

}
