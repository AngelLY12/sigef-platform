<?php

namespace App\Core\Application\UseCases\Admin\UserManagement;

use App\Core\Application\DTO\Response\User\UserExtraDataResponse;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;

class GetExtraUserDataUseCase
{
    public function __construct(
        private UserQueryRepInterface $userQueryRep,
    ){}

    public function execute(int $userId): UserExtraDataResponse
    {
        return $this->userQueryRep->getExtraUserData($userId);

    }

}
