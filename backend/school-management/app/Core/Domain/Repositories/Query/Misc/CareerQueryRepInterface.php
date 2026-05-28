<?php

namespace App\Core\Domain\Repositories\Query\Misc;

use App\Core\Domain\Entities\Career;

interface CareerQueryRepInterface
{
    public function findById(int $id): ?Career;
    public function findByName(string $careerName): ?Career;
    public function findAll(): ?array;
    public function findAllIds(): array;
    public function exists(int $id): bool;
}
