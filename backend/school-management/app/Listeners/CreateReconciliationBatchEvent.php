<?php

namespace App\Listeners;

use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Repositories\Command\Payments\PaymentEventRepInterface;
use App\Events\PaymentReconciledBatchEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateReconciliationBatchEvent implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public $queue = 'default';

    public function __construct(
        private PaymentEventRepInterface $paymentEventRep,
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentReconciledBatchEvent $event): void
    {
        $paymentEvent = PaymentEvent::createReconciliationEvent(
            paymentId: null,
            outcome: $event->outcome,
            stripeEventId: null,
            stripeSessionId: null,
            eventType: PaymentEventType::from($event->eventType),
            paymentIntentId: null,
            metadata: $event->result,
            error: $event->error,
        );
        $this->paymentEventRep->create($paymentEvent);
    }
}
