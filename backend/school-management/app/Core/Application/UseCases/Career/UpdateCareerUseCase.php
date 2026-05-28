<?php

namespace App\Core\Application\UseCases\Career;

use App\Core\Domain\Entities\Career;
use App\Core\Domain\Repositories\Command\Misc\CareerRepInterface;

class UpdateCareerUseCase
{
    public function __construct(private CareerRepInterface $careerRepo)
    {
    }

    public function execute(int $careerId, array $fields): Career
    {
        return $this->careerRepo->update($careerId, $fields);
    }
}
