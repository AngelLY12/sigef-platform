<?php

namespace App\Listeners;

use App\Core\Application\UseCases\Jobs\ProcessPaymentConceptRecipientsUseCase;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Events\PaymentConceptCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessRecipientsListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public $queue = 'default';
    public $delay = 5;
    public function __construct(
        private PaymentConceptQueryRepInterface $pcqRep,
        private ProcessPaymentConceptRecipientsUseCase $case

    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentConceptCreated $event): void
    {
        $paymentConcept = $this->pcqRep->findById($event->paymentConceptId);

        if (!$paymentConcept) {
            Log::warning('Payment concept not found for notification listener', [
                'concept_id' => $event->paymentConceptId
            ]);
            return;
        }

        $this->case->execute($paymentConcept, $event->appliesTo);

        Log::info('Payment concept recipients processed via listener', [
            'concept_id' => $event->paymentConceptId,
            'applies_to' => $event->appliesTo
        ]);
    }
    public function failed(PaymentConceptCreated $event, \Throwable $exception): void
    {
        Log::critical('ProcessRecipientsListener failed', [
            'concept_id' => $event->paymentConceptId,
            'applies_to' => $event->appliesTo,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
