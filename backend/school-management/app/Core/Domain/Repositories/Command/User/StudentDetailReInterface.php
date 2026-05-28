<?php
namespace App\Core\Domain\Repositories\Command\User;

use App\Core\Application\DTO\Request\StudentDetail\CreateStudentDetailDTO;
use App\Core\Application\DTO\Response\StudentDetail\StudentDetailDTO;
use App\Core\Domain\Entities\StudentDetail;
use App\Core\Domain\Entities\User;
use App\Models\User as ModelsUser;

interface StudentDetailReInterface
{
    public function findStudentDetails(int $userId): ?StudentDetail;
    public function findStudentDetailsToDisplay(int $userId): ?StudentDetailDTO;
    public function incrementSemesterForAll(): int;
    public function getStudentsExceedingSemesterLimit(int $maxSemester = 10): array;
    public function updateStudentDetails(int $user_id, array $fields): User;
    public function insertStudentDetails(array $studentDetails): int;
    public function insertSingleStudentDetail(array $detail): bool;
    public function attachStudentDetail(CreateStudentDetailDTO $detail, ModelsUser $user): User;
}
