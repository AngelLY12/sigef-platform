<?php

namespace App\Core\Application\UseCases\Career;

use App\Core\Domain\Entities\Career;
use App\Core\Domain\Repositories\Query\Misc\CareerQueryRepInterface;
use App\Exceptions\NotFound\CareersNotFoundException;

class FindCareerUseCase
{
    public function __construct(private CareerQueryRepInterface $careerRepo)
    {
    }

    public function execute(int $id): Career
    {
        $career= $this->careerRepo->findById($id);
        if(!$career)
        {
            throw new CareersNotFoundException();
        }
        return $career;
    }
}
