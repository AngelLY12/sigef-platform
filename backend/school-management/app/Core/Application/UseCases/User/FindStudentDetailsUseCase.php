<?php

namespace App\Core\Application\UseCases\User;

use App\Core\Application\DTO\Response\StudentDetail\StudentDetailDTO;
use App\Core\Domain\Repositories\Command\User\StudentDetailReInterface;
use App\Exceptions\NotFound\StudentDetailNotFoundException;

class FindStudentDetailsUseCase
{
    public function __construct(
        private StudentDetailReInterface $sdRepo
    ){}

    public function execute(int $userId): StudentDetailDTO
    {
        $studentDetails = $this->sdRepo->findStudentDetailsToDisplay($userId);
        if(!$studentDetails)
        {
            throw new StudentDetailNotFoundException();
        }
        return $studentDetails;
    }

}
