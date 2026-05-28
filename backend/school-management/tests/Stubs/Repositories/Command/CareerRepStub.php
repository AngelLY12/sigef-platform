<?php

namespace Tests\Stubs\Repositories\Command;

use App\Core\Domain\Repositories\Command\Misc\CareerRepInterface;
use App\Core\Domain\Entities\Career;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CareerRepStub implements CareerRepInterface
{
    private bool $throwDatabaseError = false;
    private array $careers = [];
    private int $nextId = 1;

    public function __construct()
    {
        $this->initializeTestData();
    }

    private function initializeTestData(): void
    {
        // Carreras de prueba iniciales
        $this->careers = [
            1 => new Career('Ingeniería en Sistemas', 1),
            2 => new Career('Medicina', 2),
            3 => new Career('Derecho', 3),
        ];
        $this->nextId = 4;
    }

    public function create(Career $career): Career
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        // Si la carrera ya tiene ID, usarlo; de lo contrario, generar uno nuevo
        $id = $career->id ?? $this->nextId++;

        // Crear nueva instancia con el ID asignado
        $newCareer = new Career($career->career_name, $id);

        $this->careers[$id] = $newCareer;

        return $newCareer;
    }

    public function delete(int $careerId): void
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (!isset($this->careers[$careerId])) {
            throw new ModelNotFoundException('Career not found');
        }

        unset($this->careers[$careerId]);
    }

    public function update(int $careerId, array $fields): Career
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (!isset($this->careers[$careerId])) {
            throw new ModelNotFoundException('Career not found');
        }

        $existingCareer = $this->careers[$careerId];

        // Crear una nueva instancia con los campos actualizados
        $updatedCareer = new Career(
            $fields['career_name'] ?? $existingCareer->career_name,
            $existingCareer->id
        );

        $this->careers[$careerId] = $updatedCareer;

        return $updatedCareer;
    }

    // Métodos de configuración para pruebas

    public function shouldThrowDatabaseError(bool $throw = true): self
    {
        $this->throwDatabaseError = $throw;
        return $this;
    }

    public function addCareer(Career $career): self
    {
        $id = $career->id ?? $this->nextId++;

        if ($career->id === null) {
            // Crear nueva instancia con ID asignado
            $careerWithId = new Career($career->career_name, $id);
            $this->careers[$id] = $careerWithId;
        } else {
            $this->careers[$id] = $career;
            if ($id >= $this->nextId) {
                $this->nextId = $id + 1;
            }
        }

        return $this;
    }

    public function getCareer(int $id): ?Career
    {
        return $this->careers[$id] ?? null;
    }

    public function getCareersCount(): int
    {
        return count($this->careers);
    }

    public function clearCareers(): self
    {
        $this->careers = [];
        $this->nextId = 1;
        return $this;
    }

    public function getAllCareers(): array
    {
        return $this->careers;
    }
}
