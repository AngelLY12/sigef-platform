<?php

namespace Tests\Stubs\Repositories\Command;
use App\Core\Domain\Repositories\Command\User\ParentStudentRepInterface;
use App\Core\Domain\Entities\ParentStudent;
use App\Core\Domain\Enum\User\RelationshipType;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ParentStudentRepStub implements ParentStudentRepInterface
{
    private bool $throwDatabaseError = false;
    private array $relations = [];

    public function __construct()
    {
        $this->initializeTestData();
    }

    private function initializeTestData(): void
    {
        // Relaciones de prueba iniciales
        $this->relations = [
            '100_1000' => new ParentStudent(100, 1000, 3, 4, RelationshipType::PADRE),
            '101_1001' => new ParentStudent(101, 1001, 3, 4, RelationshipType::MADRE),
            '102_1002' => new ParentStudent(102, 1002, 3, 4, RelationshipType::TUTOR),
            '103_1003' => new ParentStudent(103, 1003, 3, 4, null),
        ];
    }

    private function getKey(int $parentId, int $studentId): string
    {
        return "{$parentId}_{$studentId}";
    }

    public function create(ParentStudent $relation): ParentStudent
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        $key = $this->getKey($relation->parentId, $relation->studentId);

        if (isset($this->relations[$key])) {
            // En realidad, esto podría lanzar excepción si hay duplicado
            throw new \RuntimeException('Relation already exists');
        }

        $this->relations[$key] = $relation;

        return $relation;
    }

    public function update(int $parentId, int $studentId, array $fields): ParentStudent
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        $key = $this->getKey($parentId, $studentId);

        if (!isset($this->relations[$key])) {
            throw new ModelNotFoundException('Parent-student relation not found');
        }

        $existingRelation = $this->relations[$key];

        // Como ParentStudent tiene propiedades readonly, no podemos modificarlo directamente
        // En su lugar, creamos uno nuevo con los campos actualizados
        $updatedRelation = new ParentStudent(
            $parentId,
            $studentId,
            $existingRelation->parentRoleId,
            $existingRelation->studentRoleId,
            $fields['relationship'] ?? $existingRelation->relationship
        );

        $this->relations[$key] = $updatedRelation;

        return $updatedRelation;
    }

    public function delete(int $parentId, int $studentId): void
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        $key = $this->getKey($parentId, $studentId);

        if (!isset($this->relations[$key])) {
            throw new ModelNotFoundException('Parent-student relation not found');
        }

        unset($this->relations[$key]);
    }

    // Métodos de configuración para pruebas

    public function shouldThrowDatabaseError(bool $throw = true): self
    {
        $this->throwDatabaseError = $throw;
        return $this;
    }

    public function addRelation(ParentStudent $relation): self
    {
        $key = $this->getKey($relation->parentId, $relation->studentId);
        $this->relations[$key] = $relation;
        return $this;
    }

    public function getRelation(int $parentId, int $studentId): ?ParentStudent
    {
        $key = $this->getKey($parentId, $studentId);
        return $this->relations[$key] ?? null;
    }

    public function getRelationsCount(): int
    {
        return count($this->relations);
    }

    public function clearRelations(): self
    {
        $this->relations = [];
        return $this;
    }

    public function getAllRelations(): array
    {
        return $this->relations;
    }

    public function getStudentRelations(int $studentId): array
    {
        return array_filter($this->relations, function($relation) use ($studentId) {
            return $relation->studentId === $studentId;
        });
    }

    public function getParentRelations(int $parentId): array
    {
        return array_filter($this->relations, function($relation) use ($parentId) {
            return $relation->parentId === $parentId;
        });
    }
}
