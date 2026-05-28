<?php

namespace App\Core\Application\UseCases\Payments\Staff\Concepts;

use App\Core\Application\DTO\Response\PaymentConcept\ConceptRelationsToDisplay;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Exceptions\NotFound\ConceptNotFoundException;

class FindConceptRelationsToDisplay
{
    public function __construct(
        private PaymentConceptQueryRepInterface $pcqRepo
    )
    {
    }

    public function execute(int $id): ConceptRelationsToDisplay
    {
        $concept=  $this->pcqRepo->findRelationsByIdToDisplay($id);
        if(!$concept){
            throw new ConceptNotFoundException();
        }
        return $concept;
    }

}
