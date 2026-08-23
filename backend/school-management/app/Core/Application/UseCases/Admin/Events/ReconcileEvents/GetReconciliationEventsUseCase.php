<?php

namespace App\Core\Application\UseCases\Admin\Events\ReconcileEvents;

use App\Core\Application\DTO\Request\Events\Reconciliation\ReconciliationEventFilters;
use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Repositories\Query\Events\ReconciliationEventQueryRepInterface;

class GetReconciliationEventsUseCase
{
    public function __construct(
        private ReconciliationEventQueryRepInterface $reconciliationEventQueryRep,
    ){}

    public function execute(ReconciliationEventFilters $filters): PaginatedResponse
    {
        $reconciliationEvents = $this->reconciliationEventQueryRep->getAllReconciliationEvents($filters);
        return GeneralMapper::toPaginatedResponse($reconciliationEvents->items(), $reconciliationEvents);
    }

}
