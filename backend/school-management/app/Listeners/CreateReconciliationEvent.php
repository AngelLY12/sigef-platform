<?php

namespace App\Listeners;

use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Command\Payments\PaymentEventRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Events\PaymentReconciledEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateReconciliationEvent implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public $queue = 'default';

    public function __construct(
        private PaymentEventRepInterface $paymentEventRep,
        private PaymentQueryRepInterface $paymentQueryRep,
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentReconciledEvent $event): void
    {
        $payment = $this->paymentQueryRep->findById($event->paymentId);
        $metadata = [
            'payment_status_original' => $event->previousStatus,
            'payment_new_status' => $event->newStatus,
        ];

        if ($event->stripeData) {
            $metadata = array_merge($metadata, $event->stripeData);
        }

        if ($payment->amount_received) {
            $metadata['local_amount_received'] = $payment->amount_received;
        }

        $event = PaymentEvent::createReconciliationEvent(
            paymentId: $payment->id,
            outcome: $event->outcome,
            stripeEventId: $event->eventId,
            stripeSessionId: $event->sessionId,
            eventType: PaymentEventType::from($event->eventType),
            paymentIntentId: $payment->payment_intent_id,
            metadata: $metadata,
            amount: $payment->amount_received,
            error: $event->error,
            status: PaymentStatus::from($event->newStatus)
        );

        $this->paymentEventRep->create($event);
    }
}
