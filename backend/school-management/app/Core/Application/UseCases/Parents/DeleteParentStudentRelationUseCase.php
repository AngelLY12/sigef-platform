<?php

namespace App\Core\Application\UseCases\Parents;

use App\Core\Domain\Repositories\Command\User\ParentStudentRepInterface;
use App\Events\ParentStudentRelationDelete;

class DeleteParentStudentRelationUseCase
{
    public function __construct(
        private ParentStudentRepInterface $parentStudentRep,
    ){}

    public function execute(int $parentId, int $studentId): bool
    {
        $this->parentStudentRep->delete($parentId, $studentId);
        event(new ParentStudentRelationDelete($parentId, $studentId));
        return true;
    }

}
