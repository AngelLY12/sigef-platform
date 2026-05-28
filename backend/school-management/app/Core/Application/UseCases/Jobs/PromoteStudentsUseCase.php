<?php

namespace App\Core\Application\UseCases\Jobs;

use App\Core\Application\DTO\Response\User\PromotedStudentsResponse;
use App\Core\Application\Mappers\UserMapper;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Repositories\Command\Misc\SemesterPromotionsRepInterface;
use App\Core\Domain\Repositories\Command\User\StudentDetailReInterface;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use App\Core\Domain\Utils\Validators\SemesterPromotionValidator;
use Illuminate\Support\Facades\DB;

class PromoteStudentsUseCase
{
    public function __construct(private StudentDetailReInterface $sdRepo,
    private UserRepInterface $userRepo,
    private SemesterPromotionsRepInterface $promotionRepo )
    {
    }

    public function execute(): PromotedStudentsResponse
    {
        $wasExecuted= $this->promotionRepo->wasExecutedThisMonth();
        SemesterPromotionValidator::ensurePromotionIsValid($wasExecuted);
        [$incrementCount, $userIds] = DB::transaction(function () {
            $incrementCount = $this->sdRepo->incrementSemesterForAll();
            $maxSemester=config('promotions.max_semester');
            $userIds = $this->sdRepo->getStudentsExceedingSemesterLimit($maxSemester);
            $this->userRepo->changeStatus($userIds, UserStatus::BAJA->value);
            $this->promotionRepo->registerExecution();
            return [$incrementCount, $userIds];
        });

        return UserMapper::toPromotedStudentsResponse(
            [
                'promotedStudents' => $incrementCount,
                'desactivatedStudents' => count($userIds)
            ]
        );
    }
}
