<?php

namespace App\Core\Application\UseCases\Admin\UserManagement;

use App\Core\Application\DTO\Response\User\UsersAdminSummary;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;

class UsersAdminSummaryUseCase
{
    public function __construct(
        private UserQueryRepInterface $userQueryRep
    ){}

    public function execute(bool $onlyThisYear): UsersAdminSummary
    {
        return $this->userQueryRep->getUsersAdminSummary($onlyThisYear);
    }

}
