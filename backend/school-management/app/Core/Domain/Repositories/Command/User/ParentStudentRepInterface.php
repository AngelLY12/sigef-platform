<?php

namespace App\Core\Domain\Repositories\Command\User;

use App\Core\Domain\Entities\ParentStudent;

interface ParentStudentRepInterface
{
    public function create(ParentStudent $relation): ParentStudent;
    public function update(int $parentId, int $studentId, array $fields): ParentStudent;
    public function delete(int $parentId, int $studentId): void;
}
