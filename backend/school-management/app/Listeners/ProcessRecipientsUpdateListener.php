<?php

namespace App\Listeners;

use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptRelationsDTO;
use App\Core\Application\UseCases\Jobs\ProcessUpdateConceptRecipientsUseCase;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Events\PaymentConceptUpdatedRelations;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessRecipientsUpdateListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public $queue = 'default';
    public $delay = 5;
    public function __construct(
        private PaymentConceptQueryRepInterface $pcqRep,
        private ProcessUpdateConceptRecipientsUseCase $update
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentConceptUpdatedRelations $event): void
    {
        $newPaymentConcept = $this->pcqRep->findById($event->newPaymentConceptId);
        if (!$newPaymentConcept) {
            Log::warning('Payment concept not found for notification job', [
                'concept_id' => $event->newPaymentConceptId
            ]);
            return;
        }
        if(!$event->oldPaymentConceptArray)
        {
            Log::warning('Payment concept not found for notification job', [
                'concept_id' => $event->newPaymentConceptId
            ]);
            return;
        }
        $oldPaymentConcept = PaymentConcept::fromArray($event->oldPaymentConceptArray);
        $dto=UpdatePaymentConceptRelationsDTO::fromArray($event->dtoArray);
        $this->update->execute($newPaymentConcept, $oldPaymentConcept, $event->oldRecipientIds ,$dto, $event->appliesTo);
        Log::info('Payment concept recipients processed', [
            'concept_id' => $event->newPaymentConceptId,
            'applies_to' => $event->appliesTo
        ]);
    }

    public function failed(PaymentConceptUpdatedRelations $event, \Throwable $exception): void
    {
        Log::critical('ProcessPaymentConceptRecipientsListener failed', [
            'concept_id' => $event->newPaymentConceptId,
            'applies_to' => $event->appliesTo,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
