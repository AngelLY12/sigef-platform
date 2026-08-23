<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Application\Factories\Payments\Stripe\WebhookPaymentIntentEventFactory;
use App\Core\Application\Services\Events\Contracts\PaymentEventManagerInterface;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Exceptions\NotFound\PaymentNotFountException;
use Stripe\PaymentIntent;

class PaymentIntentSucceededUseCase
{
    public function __construct(
        private PaymentQueryRepInterface $paymentQueryRep,
        private PaymentEventManagerInterface $paymentEventManager
    ){}

    public function execute(PaymentIntent $paymentIntent, string $eventId): bool
    {
        $payment = $this->paymentQueryRep->findById($paymentIntent->metadata->payment_id);
        if (!$payment) {
            throw new PaymentNotFountException();
        }
        $event = $this->paymentEventManager->findOrCreate(
            stripeEventId: $eventId,
            eventType: PaymentEventType::WEBHOOK_CHARGE_SUCCEEDED,
            factory: fn () => WebhookPaymentIntentEventFactory::succeeded($eventId, $payment, $paymentIntent)
        );
        if ($event->processed) {
            return true;
        }
        $this->paymentEventManager->process(
            event: $event,
            callback: function () use (
                $event,
                $payment,
                $eventId
            ) {
                $this->processIntent(
                    event: $event,
                    payment: $payment,
                );
            }
        );

        return true;
    }
    private function processIntent(
        PaymentEvent $event,
        Payment $payment,
    ): void {

        $event->setStatus($payment->status);
        $event->setAmountReceived($payment->amount_received ?? '0.00');
    }

}
