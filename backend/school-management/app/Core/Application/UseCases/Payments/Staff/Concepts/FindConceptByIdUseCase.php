<?php

namespace App\Core\Application\UseCases\Payments\Staff\Concepts;

use App\Core\Application\DTO\Response\PaymentConcept\ConceptToDisplay;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Exceptions\NotFound\ConceptNotFoundException;

class FindConceptByIdUseCase
{
    public function __construct(
        private PaymentConceptQueryRepInterface $pcqRepo
    )
    {
    }

    public function execute(int $id): ConceptToDisplay
    {
        $concept=  $this->pcqRepo->findByIdToDisplay($id);
        if(!$concept){
            throw new ConceptNotFoundException();
        }
        return $concept;
    }
}
