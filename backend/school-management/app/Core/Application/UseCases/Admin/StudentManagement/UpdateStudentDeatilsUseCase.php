<?php

namespace App\Core\Application\UseCases\Admin\StudentManagement;

use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Command\User\StudentDetailReInterface;
use App\Jobs\ClearStaffCacheJob;

class UpdateStudentDeatilsUseCase
{
    public function __construct(
        private StudentDetailReInterface $repo
    )
    {
    }

    public function execute(int $userId, array $fields): User
    {
        $update=$this->repo->updateStudentDetails($userId, $fields);
        ClearStaffCacheJob::dispatch()->onQueue('cache');
        return $update;
    }
}
