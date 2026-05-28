<?php

namespace App\Core\Application\UseCases\Jobs;

use App\Core\Domain\Repositories\Command\Payments\PaymentConceptRepInterface;

class CleanDeletedConceptsUseCase
{
    public function __construct(
        private PaymentConceptRepInterface $pcRepo
    )
    {
    }

    public function execute():int
    {
        return $this->pcRepo->cleanDeletedConcepts();
    }
}
