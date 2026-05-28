<?php

namespace App\Core\Application\UseCases\Payments\Staff\Concepts;

use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;

class FindControlNumbersBySearchUseCase
{
    public function __construct(
        private UserQueryRepInterface $userQueryRep,
    ){}

    public function execute(string $search, int $limit): array
    {
        return $this->userQueryRep->getControlNumbersBySearch($search, $limit);
    }

}
