<?php

namespace App\Core\Application\UseCases\Admin\Events\EmailEvents;

use App\Core\Application\DTO\Request\Events\EmailEvent\EmailEventFilters;
use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Repositories\Query\Events\EmailEventQueryRepInterface;

class GetEmailEventsUseCase
{
    public function __construct(
        private EmailEventQueryRepInterface $emailEventQueryRep,
    ){}

    public function execute(EmailEventFilters $filters): PaginatedResponse
    {
        $emailEvents = $this->emailEventQueryRep->getAllEmailEvents($filters);
        return GeneralMapper::toPaginatedResponse($emailEvents->items(), $emailEvents);
    }

}
