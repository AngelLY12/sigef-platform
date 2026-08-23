<?php

namespace App\Core\Application\UseCases\Admin\Events\ReconcileEvents;

use App\Core\Application\DTO\Response\Events\ReconcileEvent\ReconcileEventByIdResponse;
use App\Core\Domain\Repositories\Query\Events\ReconciliationEventQueryRepInterface;
use App\Exceptions\NotFound\NotFoundException;

class GetReconciliatonEventByIdUseCase
{
    public function __construct(
        private ReconciliationEventQueryRepInterface $reconciliationEventQueryRep,
    ){}

    public function execute(int $id): ReconcileEventByIdResponse
    {
        $event = $this->reconciliationEventQueryRep->findById($id);
        if(!$event){
            throw new NotFoundException('El evento de reconciliación solicitado no fue encontrado.');
        }
        return ReconcileEventByIdResponse::create($event);
    }

}
