<?php

namespace App\Core\Application\UseCases\Admin\StudentManagement;

use App\Core\Domain\Entities\StudentDetail;
use App\Core\Domain\Repositories\Command\User\StudentDetailReInterface;
use App\Exceptions\NotFound\StudentDetailNotFoundException;

class FindStudentDetailUseCase
{
    public function __construct(
        private StudentDetailReInterface $repo
    )
    {
    }

    public function execute(int $user_id): StudentDetail
    {
        $sd=$this->repo->findStudentDetails($user_id);
        if(!$sd)
        {
            throw new StudentDetailNotFoundException();
        }
        return $sd;
    }
}
