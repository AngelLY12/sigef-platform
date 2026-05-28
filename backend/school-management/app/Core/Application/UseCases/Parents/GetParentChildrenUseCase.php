<?php

namespace App\Core\Application\UseCases\Parents;

use App\Core\Application\DTO\Response\Parents\ParentChildrenResponse;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Query\User\ParentStudentQueryRepInterface;
use App\Exceptions\NotAllowed\UserInvalidRoleException;
use App\Exceptions\NotFound\ParentChildrenNotFoundException;

class GetParentChildrenUseCase
{
    public function __construct(
        private ParentStudentQueryRepInterface $relationQRepo,
    ) {}

    public function execute(User $parent): ParentChildrenResponse
    {
        if (!$parent->isParent()) {
            throw new UserInvalidRoleException();
        }
        $response=$this->relationQRepo->getStudentsOfParent($parent->id);
        if(!$response)
        {
            throw new ParentChildrenNotFoundException();
        }

        return $response;
    }
}
