<?php

namespace App\Core\Application\UseCases\Parents;

use App\Core\Application\DTO\Response\Parents\StudentParentsResponse;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Query\User\ParentStudentQueryRepInterface;
use App\Exceptions\NotAllowed\UserInvalidRoleException;
use App\Exceptions\NotFound\StudentParentsNotFoundException;

class GetStudentParentsUseCase
{
    public function __construct(
        private ParentStudentQueryRepInterface $psqRepo
    )
    {
    }

    public function execute(User $student): StudentParentsResponse
    {
        if(!$student->isStudent())
        {
            throw new UserInvalidRoleException();
        }
        $parents= $this->psqRepo->getParentsOfStudent($student->id);
        if($parents==null)
        {
            throw new StudentParentsNotFoundException();
        }
        return $parents;
    }

}
