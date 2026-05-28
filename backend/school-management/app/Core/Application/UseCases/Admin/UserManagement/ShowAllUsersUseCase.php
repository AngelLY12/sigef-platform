<?php

namespace App\Core\Application\UseCases\Admin\UserManagement;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;

class ShowAllUsersUseCase
{
    public function __construct(
        private UserQueryRepInterface $uqRepo
    )
    {
    }

    public function execute(int $perPage, int $page, ?UserStatus $status=null): PaginatedResponse
    {
        $users= $this->uqRepo->findAllUsers($perPage, $page, $status);
        return GeneralMapper::toPaginatedResponse($users->items(), $users);
    }
}
