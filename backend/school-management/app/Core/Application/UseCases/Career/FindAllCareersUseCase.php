<?php

namespace App\Core\Application\UseCases\Career;

use App\Core\Domain\Repositories\Query\Misc\CareerQueryRepInterface;

class FindAllCareersUseCase
{
    public function __construct(private CareerQueryRepInterface $careerRepo)
    {
    }

    public function execute(): array
    {
        return $this->careerRepo->findAll();
    }
}
