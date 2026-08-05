<?php

namespace App\Core\Domain\Repositories\Query\User;

use App\Core\Application\DTO\Response\Parents\ParentChildrenResponse;
use App\Core\Application\DTO\Response\Parents\StudentParentsResponse;
use App\Core\Domain\Entities\User;

interface ParentStudentQueryRepInterface
{
    public function getStudentsOfParent(User $parent): ParentChildrenResponse;
    public function getParentsOfStudent(User $student): StudentParentsResponse;
    public function exists(int $parentId, int $studentId): bool;
}
