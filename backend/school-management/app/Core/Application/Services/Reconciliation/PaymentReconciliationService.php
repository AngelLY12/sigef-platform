<?php

namespace App\Core\Application\Services\Reconciliation;

use App\Core\Application\DTO\Response\Payment\PaymentReconciliationResponse;
use App\Core\Application\Factories\Payments\Stripe\StripePaymentMethodDetailsFactory;
use App\Core\Application\Services\Events\Contracts\ReconciliationEventManagerInterface;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Entities\ReconciliationEvent;
use App\Core\Domain\Enum\Events\ReconciliationOutcome;
use App\Core\Domain\Enum\Events\Sources\ReconciliationDataSource;
use App\Core\Domain\Enum\Events\Sources\ReconciliationSourceType;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayQueryInterface;
use App\Core\Domain\Utils\Helpers\EventSourceId;
use App\Core\Domain\Utils\Helpers\Money;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Charge\ChargeSucceededData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentSucceededData;
use App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails\PaymentMethodDetails;
use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationCorrectedData;
use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationMatchedData;
use App\Exceptions\BadRequest;
use Illuminate\Support\Collection;
use Stripe\Charge;

class PaymentReconciliationService implements PaymentReconciliationServiceInterface
{
    public function __construct(
        private PaymentRepInterface $paymentRepo,
        private StripeGatewayQueryInterface $gatewayQuery,
        private PaymentMethodQueryRepInterface $paymentMethodQueryRep,
        private ReconciliationEventManagerInterface $eventManager,
    )
    {
    }


    public function reconcile(Payment $payment, Collection $events, string $operationId): PaymentReconciliationResponse
    {
        $response = PaymentReconciliationResponse::create(paymentId: $payment->id);
        $sourceId = EventSourceId::reconciliation(
            sourceType: ReconciliationSourceType::MANUAL,
            operationId: $operationId
        );

        $reconciliationEvent = $this->eventManager->create(
            paymentId: $payment->id,
            sourceType: ReconciliationSourceType::MANUAL,
            sourceId: $sourceId,
        );

        try {
            $eventsByType = $events->groupBy(
                fn (PaymentEvent $event) => $event->eventType->value
            );

            $chargeEvents = $eventsByType->get(
                PaymentEventType::WEBHOOK_CHARGE_SUCCEEDED->value,
                collect()
            );

            if ($chargeEvents->isNotEmpty()) {
                $response = $this->reconcileFromChargeEvents(
                    payment: $payment,
                    events: $chargeEvents,
                    reconciliationEvent: $reconciliationEvent,
                    response: $response,
                );

                if ($response->reconciled) {
                    return $response;
                }
            }

            $paymentIntentEvents = $eventsByType->get(
                PaymentEventType::WEBHOOK_PAYMENT_INTENT_SUCCEEDED->value,
                collect()
            );

            if ($paymentIntentEvents->isNotEmpty()) {
                $response = $this->reconcileFromPaymentIntentEvents(
                    payment: $payment,
                    events: $paymentIntentEvents,
                    reconciliationEvent: $reconciliationEvent,
                    response: $response,
                );

                if ($response->reconciled) {
                    return $response;
                }
            }

            $sessionEvents = $eventsByType->get(
                PaymentEventType::WEBHOOK_SESSION_COMPLETED->value,
                collect()
            );

            if ($sessionEvents->isNotEmpty()) {
                $response = $this->reconcileFromSessionEvents(
                    payment: $payment,
                    events: $sessionEvents,
                    reconciliationEvent: $reconciliationEvent,
                    response: $response,
                );

                if ($response->reconciled) {
                    return $response;
                }
            }
        }catch (\Throwable $exception){
            $this->eventManager->fail(
                event: $reconciliationEvent,
                error: $exception->getMessage(),
                outcome: ReconciliationOutcome::FAILED,
            );
            $response->failedReconciliation('Ocurrió un error durante la reconciliación.');
            throw new BadRequest($response->message);
        }
        $errorMessage = 'No se encontraron eventos suficientes para reconciliar el pago.';
        $response->failedReconciliation($errorMessage);
        $this->eventManager->fail(
            event: $reconciliationEvent,
            error: $errorMessage,
            outcome: ReconciliationOutcome::MISMATCH,
        );

        return $response;

    }
    private function reconcileFromChargeEvents(
        Payment $payment,
        Collection $events,
        ReconciliationEvent $reconciliationEvent,
        PaymentReconciliationResponse $response
    ): PaymentReconciliationResponse {
        $charges = $events->map(fn (PaymentEvent $event) => $event->metadata)
            ->filter(fn ($metadata) => $metadata instanceof ChargeSucceededData);

        if($charges->isEmpty()) {
            return $response;
        }

        $received = $this->calculateReceivedAmount(
            $charges->map(fn (ChargeSucceededData $charge) => $charge->amountCaptured)
        );

        if (!$this->isAmountSufficient($payment, $received)) {
            return $response;
        }

        /** @var PaymentEvent $lastEvent */
        $lastEvent = $events->first();
        /** @var ChargeSucceededData $lastCharge */
        $lastCharge = $lastEvent->metadata;

        return $this->completeReconciliation(
            payment: $payment,
            reconciliationEvent: $reconciliationEvent,
            chargeId: $lastCharge->chargeId,
            paymentIntentId: $lastEvent->stripePaymentIntentId,
            paymentMethodId: $lastCharge->paymentMethodId,
            stripePaymentMethodId: $lastCharge->stripePaymentMethodId,
            received: $received,
            paymentMethodDetails: $lastCharge->paymentMethodDetails,
            receiptUrl: $lastCharge->receiptUrl,
            response: $response,
            source: ReconciliationDataSource::CHARGE,
        );
    }


    private function reconcileFromPaymentIntentEvents(
        Payment $payment,
        Collection $events,
        ReconciliationEvent $reconciliationEvent,
        PaymentReconciliationResponse $response
    ): PaymentReconciliationResponse {
        $paymentIntentData = $events->mapWithKeys(fn (PaymentEvent $event) => [
            $event->stripePaymentIntentId => $event->metadata
        ]);

        $chargeIds = $paymentIntentData
            ->pluck('latestCharge')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($chargeIds)) {
            return $response;
        }

        $charges = $this->gatewayQuery->getChargesByIds($chargeIds);

        $received = $this->calculateReceivedAmount(
            $charges->map(fn (Charge $charge) => $charge->amount_captured));

        if (!$this->isAmountSufficient($payment, $received)) {
            return $response;
        }

        /** @var PaymentEvent $lastEvent */
        $lastEvent = $events->first();

        /** @var PaymentIntentSucceededData $lastPaymentIntent */
        $lastPaymentIntent = $lastEvent->metadata;

        /** @var Charge|null $lastCharge */
        $lastCharge = $charges->first(
            fn (Charge $charge) =>
                $charge->id === $lastPaymentIntent->latestCharge
        );

        if (!$lastCharge) {
            return $response;
        }

        $paymentMethod = $this->paymentMethodQueryRep->findByStripeId(
            $lastCharge->payment_method
        );

        return $this->completeReconciliation(
            payment: $payment,
            reconciliationEvent: $reconciliationEvent,
            chargeId: $lastCharge->id,
            paymentIntentId: $lastCharge->payment_intent,
            paymentMethodId: $paymentMethod?->id,
            stripePaymentMethodId: $lastCharge->payment_method,
            received: $received,
            paymentMethodDetails: StripePaymentMethodDetailsFactory::fromStripe(
                $lastCharge->payment_method_details
            ),
            receiptUrl: $lastCharge->receipt_url,
            response: $response,
            source: ReconciliationDataSource::PAYMENT_INTENT,
        );
    }

    private function reconcileFromSessionEvents(
        Payment $payment,
        Collection $events,
        ReconciliationEvent $reconciliationEvent,
        PaymentReconciliationResponse $response
    ): PaymentReconciliationResponse {
        $paymentIntentIds = $events
            ->map(fn (PaymentEvent $event) => $event->stripePaymentIntentId)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($paymentIntentIds)) {
            return $response;
        }

        $charges = $this->gatewayQuery->getChargesByIntentIds($paymentIntentIds);

        $received = $this->calculateReceivedAmount(
            $charges->map(fn (Charge $charge) => $charge->amount_captured)
        );

        if (!$this->isAmountSufficient($payment, $received)) {
            return $response;
        }

        /** @var PaymentEvent $lastEvent */
        $lastEvent = $events->first();
        $lastPaymentIntentId = $lastEvent->stripePaymentIntentId;
        /** @var Charge|null $lastCharge */
        $lastCharge = $charges->first(
            fn (Charge $charge) => $charge->payment_intent === $lastPaymentIntentId
        );

        if (!$lastCharge) {
            return $response;
        }

        $paymentMethod = $this->paymentMethodQueryRep->findByStripeId(
            $lastCharge->payment_method
        );

        return $this->completeReconciliation(
            payment: $payment,
            reconciliationEvent: $reconciliationEvent,
            chargeId: $lastCharge->id,
            paymentIntentId: $lastCharge->payment_intent,
            paymentMethodId: $paymentMethod?->id,
            stripePaymentMethodId: $lastCharge->payment_method,
            received: $received,
            paymentMethodDetails: StripePaymentMethodDetailsFactory::fromStripe(
                $lastCharge->payment_method_details
            ),
            receiptUrl: $lastCharge->receipt_url,
            response: $response,
            source: ReconciliationDataSource::CHECKOUT_SESSION,
        );
    }

    private function calculateReceivedAmount(Collection $amounts): Money
    {
        $received = Money::from('0');

        foreach ($amounts as $amount) {
            $received = $received->add(Money::from($amount)->divide('100'));
        }
        return $received;
    }

    private function isAmountSufficient(
        Payment $payment,
        Money $received
    ): bool {
        return !$received->isLessThan(Money::from($payment->amount));
    }

    private function completeReconciliation(
        Payment $payment,
        ReconciliationEvent $reconciliationEvent,
        string $chargeId,
        string $paymentIntentId,
        ?string $paymentMethodId,
        ?string $stripePaymentMethodId,
        Money $received,
        ?PaymentMethodDetails $paymentMethodDetails,
        ?string $receiptUrl,
        PaymentReconciliationResponse $response,
        ReconciliationDataSource $source,
    ): PaymentReconciliationResponse {
        $expected = Money::from($payment->amount);
        $status = $received->isGreaterThan($expected)
            ? PaymentStatus::OVERPAID
            : PaymentStatus::SUCCEEDED;

        $hasChanges = $this->hasChanges(
            payment: $payment,
            paymentIntentId: $paymentIntentId,
            paymentMethodId: $paymentMethodId,
            stripePaymentMethodId: $stripePaymentMethodId,
            received: $received,
            status: $status,
            receiptUrl: $receiptUrl,
        );

        if($hasChanges) {
            $fields = [
                'payment_intent_id' => $paymentIntentId,
                'payment_method_id' => $paymentMethodId,
                'stripe_payment_method_id' => $stripePaymentMethodId,
                'amount_received' => $received->finalize(),
                'status' => $status,
                'payment_method_details' => $paymentMethodDetails,
                'url' => $receiptUrl ?? $payment->url,
            ];

            $this->paymentRepo->update($payment->id, $fields);

            $this->eventManager->complete(
                event: $reconciliationEvent,
                outcome: ReconciliationOutcome::CORRECTED,
                metadata: ReconciliationCorrectedData::create(
                    dataSource: $source,
                    amountReceived: $received->finalize(),
                    paymentIntentId: $paymentIntentId,
                    chargeId: $chargeId,
                )
            );

            $response->processReconciliation(
                source: $source->label(),
                changes: [
                    'monto_recibido' => true,
                    'estado' => true,
                    'metodo_pago' => true,
                    'datos_pago' => true,
                ],
                message: 'El pago fue reconciliado correctamente.',
            );
        }else{
            $this->eventManager->complete(
                event: $reconciliationEvent,
                outcome: ReconciliationOutcome::MATCHED,
                metadata: ReconciliationMatchedData::create(
                    dataSource: $source,
                )
            );

            $response->processReconciliation(
                source: $source->label(),
                changes: [
                    'sin_cambio' => true,
                ],
                message: 'Los datos ya estaban sincronizados.',
            );

        }

        return $response;
    }

    private function hasChanges(
        Payment $payment,
        string $paymentIntentId,
        ?string $paymentMethodId,
        ?string $stripePaymentMethodId,
        Money $received,
        PaymentStatus $status,
        ?string $receiptUrl,
    ): bool {
        $changes = [
            $payment->payment_intent_id !== $paymentIntentId,

            $payment->payment_method_id !== $paymentMethodId,

            $payment->stripe_payment_method_id !== $stripePaymentMethodId,

            $payment->amount_received !== $received->finalize(),

            $payment->status !== $status,

            $payment->url !== ($receiptUrl ?? $payment->url),
        ];

        return collect($changes)->contains(true);
    }

}
