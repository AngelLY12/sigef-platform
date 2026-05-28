<?php

namespace App\Core\Infraestructure\Repositories\Command\User;

use App\Core\Application\DTO\Request\StudentDetail\CreateStudentDetailDTO;
use App\Core\Application\DTO\Response\StudentDetail\StudentDetailDTO;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Entities\StudentDetail;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Repositories\Command\User\StudentDetailReInterface;
use App\Models\StudentDetail as EloquentStudentDetail;
use App\Core\Infraestructure\Mappers\StudentDetailMapper;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Models\User as ModelsUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class EloquentStudentDetailRepository implements StudentDetailReInterface
{
    public function findStudentDetails(int $userId): ?StudentDetail
    {
        $eloquentStudentDetails = EloquentStudentDetail::where('user_id',$userId)->first();
        return $eloquentStudentDetails ? StudentDetailMapper::toDomain($eloquentStudentDetails): null;
    }

    public function findStudentDetailsToDisplay(int $userId): ?StudentDetailDTO
    {
        $eloquentStudentDetails = EloquentStudentDetail::with('career:id,career_name')
            ->where('user_id', $userId)
            ->first();

        return $eloquentStudentDetails
            ? \App\Core\Application\Mappers\StudentDetailMapper::toStudentDetailDTO($eloquentStudentDetails)
            : null;
    }

    public function insertStudentDetails(array $studentDetails): int {
        if (!empty($studentDetails)) {
            $result = DB::table('student_details')->insert($studentDetails);
            return $result ? count($studentDetails) : 0;
        }
        return 0;
    }

    public function insertSingleStudentDetail(array $detail): bool
    {
        try {
            DB::table('student_details')->insert($detail);
            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function incrementSemesterForAll(): int
    {
        return EloquentStudentDetail::where('semestre', '<', 11)
            ->whereHas('user', function ($query) {
                $query->whereIn('status', [UserStatus::ACTIVO, UserStatus::BAJA_TEMPORAL]);
            })
            ->increment('semestre');
    }

    public function getStudentsExceedingSemesterLimit(int $maxSemester = 10): array
    {
        return EloquentStudentDetail::where('semestre', '>', $maxSemester)
            ->whereHas('user', function ($query) {
                $query->whereIn('status', [UserStatus::ACTIVO, UserStatus::BAJA_TEMPORAL]);
            })
            ->pluck('user_id')
            ->toArray();
    }


    public function updateStudentDetails(int $user_id, array $fields): User
    {
        $model= $this->findModelByUserId($user_id);
        $model->update($fields);
        unset($model->user->studentDetail);
        $model->user->load('studentDetail');
        return UserMapper::toDomain($model->user);
    }

    public function attachStudentDetail(CreateStudentDetailDTO $detail, ModelsUser $user): User
    {
        $user->studentDetail()->create(
            StudentDetailMapper::toPersistence($detail)
        );
        $user->load('studentDetail');
        $user->syncRoles([UserRoles::STUDENT->value]);
        return UserMapper::toDomain($user);
    }

    private function findModelByUserId(int $user_id): EloquentStudentDetail
    {
        return EloquentStudentDetail::where('user_id', $user_id)->firstOrFail();
    }

}
