<?php

namespace App\Core\Application\UseCases\Career;

use App\Core\Domain\Repositories\Command\Misc\CareerRepInterface;

class DeleteCareerUseCase
{
    public function __construct(private CareerRepInterface $careerRepo)
    {
    }

    public function execute(int $careerId): void
    {
        $this->careerRepo->delete($careerId);
    }
}
