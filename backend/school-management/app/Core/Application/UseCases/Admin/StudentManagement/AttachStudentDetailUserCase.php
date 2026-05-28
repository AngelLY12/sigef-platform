<?php

namespace App\Core\Application\UseCases\Admin\StudentManagement;

use App\Core\Application\DTO\Request\StudentDetail\CreateStudentDetailDTO;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Command\User\StudentDetailReInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Exceptions\Conflict\UserAlreadyHaveStudentDetailException;
use App\Exceptions\Validation\ValidationException;
use App\Jobs\ClearStaffCacheJob;

class AttachStudentDetailUserCase
{
    public function __construct(
        private UserQueryRepInterface $userRepo,
        private StudentDetailReInterface $sdRepo
    )
    {
    }

    public function execute(CreateStudentDetailDTO $detail):User
    {
        $user=$this->userRepo->findModelEntity($detail->user_id);
        $maxSemester=config('promotions.max_semester');
        if($detail->semestre > $maxSemester)
        {
            throw new ValidationException("El semestre no es valido, debe ser menor o igual a {$maxSemester}" );
        }
        if($user->studentDetail()->exists())
        {
            throw new UserAlreadyHaveStudentDetailException();
        }
        $updatedUser=$this->sdRepo->attachStudentDetail($detail,$user);
        ClearStaffCacheJob::dispatch()
            ->onQueue('cache');
        return $updatedUser;
    }
}
