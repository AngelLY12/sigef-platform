<?php

namespace App\Core\Application\UseCases\Admin\Events\EmailEvents;

use App\Core\Application\DTO\Request\Events\EmailEvent\EmailEventHistoryFilters;
use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Repositories\Query\Events\EmailEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Exceptions\NotFound\UserNotFoundException;

class GetUserEmailHistoryUseCase
{
    public function __construct(
        private EmailEventQueryRepInterface $emailEventQueryRep,
        private UserQueryRepInterface $userQueryRep
    ){}

    public function execute(EmailEventHistoryFilters $filters, int $userId): PaginatedResponse
    {
        if(!$this->userQueryRep->existsById($userId))
        {
            throw new UserNotFoundException();
        }
        $history = $this->emailEventQueryRep->getUserEmailHistory($filters,$userId);
        return GeneralMapper::toPaginatedResponse($history->items(), $history);
    }

}
