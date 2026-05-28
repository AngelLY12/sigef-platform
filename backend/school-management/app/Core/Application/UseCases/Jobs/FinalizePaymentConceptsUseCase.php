<?php

namespace App\Core\Application\UseCases\Jobs;

use App\Core\Domain\Repositories\Command\Payments\PaymentConceptRepInterface;
use App\Events\PaymentConceptStatusChanged;
use Illuminate\Support\Facades\Log;

class FinalizePaymentConceptsUseCase
{
    public function __construct(
        private PaymentConceptRepInterface $finalize
    )
    {

    }

    public function execute(): int
    {
        $updatedConcepts=$this->finalize->finalizePaymentConcepts();
        $count = count($updatedConcepts);
        if ($count > 0) {
            Log::info('Payment concepts finalized', [
                'count' => $count,
                'concept_ids' => array_column($updatedConcepts, 'id'),
                'executed_at' => now()->toDateTimeString()
            ]);
        }

        foreach ($updatedConcepts as $concept) {
            event(new PaymentConceptStatusChanged(
                $concept['id'],
                $concept['old_status'],
                $concept['new_status']
            ));
        }

        return $count;
    }
}
