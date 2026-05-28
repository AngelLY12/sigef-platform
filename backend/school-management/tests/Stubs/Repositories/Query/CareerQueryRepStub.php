<?php

namespace Tests\Stubs\Repositories\Query;

use App\Core\Domain\Repositories\Query\Misc\CareerQueryRepInterface;
use App\Core\Domain\Entities\Career;

class CareerQueryRepStub implements CareerQueryRepInterface
{
    private ?Career $nextFindByIdResult = null;
    private ?Career $nextFindByNameResult = null;
    private ?array $nextFindAllResult = null;

    public function findById(int $id): ?Career
    {
        return $this->nextFindByIdResult;
    }

    public function findByName(string $careerName): ?Career
    {
        return $this->nextFindByNameResult;
    }

    public function findAll(): ?array
    {
        return $this->nextFindAllResult;
    }

    // Métodos de configuración
    public function setNextFindByIdResult(?Career $career): self
    {
        $this->nextFindByIdResult = $career;
        return $this;
    }

    public function setNextFindByNameResult(?Career $career): self
    {
        $this->nextFindByNameResult = $career;
        return $this;
    }

    public function setNextFindAllResult(?array $careers): self
    {
        $this->nextFindAllResult = $careers;
        return $this;
    }
}
