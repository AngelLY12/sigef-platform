<?php

namespace App\Core\Application\UseCases\Career;

use App\Core\Domain\Entities\Career;
use App\Core\Domain\Repositories\Command\Misc\CareerRepInterface;
use Illuminate\Support\Facades\DB;

class CreateCareerUseCase
{
    public function __construct(private CareerRepInterface $careerRepo)
    {
    }

    public function execute(Career $career): Career
    {
        return DB::transaction(function() use ($career) {
            return $this->careerRepo->create($career);
        });
    }
}
