<?php

namespace App\Core\Domain\Repositories\Query\User;

use App\Core\Application\DTO\Response\Parents\ParentChildrenResponse;
use App\Core\Application\DTO\Response\Parents\StudentParentsResponse;

interface ParentStudentQueryRepInterface
{
    public function getStudentsOfParent(int $parentId): ?ParentChildrenResponse;
    public function getParentsOfStudent(int $studentId): ?StudentParentsResponse;
    public function exists(int $parentId, int $studentId): bool;
}
