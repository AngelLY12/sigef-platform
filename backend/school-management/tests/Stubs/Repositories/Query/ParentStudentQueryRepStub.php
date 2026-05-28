<?php

namespace Tests\Stubs\Repositories\Query;

use App\Core\Domain\Repositories\Query\User\ParentStudentQueryRepInterface;
use App\Core\Application\DTO\Response\Parents\ParentChildrenResponse;
use App\Core\Application\DTO\Response\Parents\StudentParentsResponse;

class ParentStudentQueryRepStub implements ParentStudentQueryRepInterface
{
    private ?ParentChildrenResponse $nextGetStudentsOfParentResult = null;
    private ?StudentParentsResponse $nextGetParentsOfStudentResult = null;
    private bool $nextExistsResult = false;

    public function getStudentsOfParent(int $parentId): ?ParentChildrenResponse
    {
        return $this->nextGetStudentsOfParentResult;
    }

    public function getParentsOfStudent(int $studentId): ?StudentParentsResponse
    {
        return $this->nextGetParentsOfStudentResult;
    }

    public function exists(int $parentId, int $studentId): bool
    {
        return $this->nextExistsResult;
    }

    // Métodos de configuración
    public function setNextGetStudentsOfParentResult(?ParentChildrenResponse $response): self
    {
        $this->nextGetStudentsOfParentResult = $response;
        return $this;
    }

    public function setNextGetParentsOfStudentResult(?StudentParentsResponse $response): self
    {
        $this->nextGetParentsOfStudentResult = $response;
        return $this;
    }

    public function setNextExistsResult(bool $exists): self
    {
        $this->nextExistsResult = $exists;
        return $this;
    }
}
