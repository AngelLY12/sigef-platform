<?php

namespace Tests\Stubs\Repositories\Command;
use App\Core\Application\DTO\Request\StudentDetail\CreateStudentDetailDTO;
use App\Core\Application\DTO\Response\StudentDetail\StudentDetailDTO;
use App\Core\Domain\Entities\StudentDetail;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Command\User\StudentDetailReInterface;
use App\Models\User as ModelsUser;

class StudentDetailRepositoryStub implements StudentDetailReInterface
{
    private array $studentDetails = [];
    private array $users = [];
    private int $incrementCount = 0;
    private StudentDetailDTO $nextStudentDetail;

    public function __construct()
    {
        // Datos de ejemplo para testing
        $this->seedInitialData();
    }

    private function seedInitialData(): void
    {
        // Usuario 1 con detalles de estudiante
        $this->studentDetails[1] = new StudentDetail(
            user_id: 1,
            id: 1,
            career_id: 10,
            n_control: 20201234,
            semestre: 5,
            group: 'A',
            workshop: 'Taller de programación'
        );

        // Usuario 2 sin detalles de estudiante
        $this->studentDetails[2] = null;

        // Usuario 3 con semestre mayor al límite
        $this->studentDetails[3] = new StudentDetail(
            user_id: 3,
            id: 3,
            career_id: 11,
            n_control: 20201235,
            semestre: 12, // Excede el límite
            group: 'B',
            workshop: 'Taller de matemáticas'
        );
    }

    public function findStudentDetails(int $userId): ?StudentDetail
    {
        return $this->studentDetails[$userId] ?? null;
    }

    public function incrementSemesterForAll(): int
    {
        $count = 0;
        foreach ($this->studentDetails as $userId => $detail) {
            if ($detail instanceof StudentDetail && $detail->semestre <= 10) {
                $detail->promote();
                $count++;
            }
        }
        $this->incrementCount += $count;
        return $count;
    }

    public function getStudentsExceedingSemesterLimit(int $maxSemester = 10): array
    {
        $exceedingStudents = [];

        foreach ($this->studentDetails as $userId => $detail) {
            if ($detail instanceof StudentDetail && $detail->semestre > $maxSemester) {
                $exceedingStudents[] = $userId;
            }
        }

        return $exceedingStudents;
    }

    public function findStudentDetailsToDisplay(int $userId): ?StudentDetailDTO
    {
        return $this->nextStudentDetail;
    }

    public function updateStudentDetails(int $user_id, array $fields): User
    {
        if (!isset($this->studentDetails[$user_id])) {
            throw new \RuntimeException("No se encontraron detalles de estudiante para el usuario $user_id");
        }

        $detail = $this->studentDetails[$user_id];

        // Actualizar campos
        foreach ($fields as $field => $value) {
            if (property_exists($detail, $field)) {
                $detail->$field = $value;
            }
        }

        // Crear y retornar un User simulado
        return $this->createMockUser($user_id, $detail);
    }

    public function insertStudentDetails(array $studentDetails): int
    {
        if (empty($studentDetails)) {
            return 0;
        }

        $count = 0;
        foreach ($studentDetails as $detail) {
            if (is_array($detail) && isset($detail['user_id'])) {
                $newId = count($this->studentDetails) + 1;
                $this->studentDetails[$detail['user_id']] = new StudentDetail(
                    user_id: $detail['user_id'],
                    id: $newId,
                    career_id: $detail['career_id'] ?? null,
                    n_control: $detail['n_control'] ?? null,
                    semestre: $detail['semestre'] ?? null,
                    group: $detail['group'] ?? null,
                    workshop: $detail['workshop'] ?? null
                );
                $count++;
            }
        }

        return $count;
    }

    public function insertSingleStudentDetail(array $detail): bool
    {
        if (!isset($detail['user_id'])) {
            throw new \InvalidArgumentException("El array de detalles debe contener 'user_id'");
        }

        $newId = count($this->studentDetails) + 1;
        $this->studentDetails[$detail['user_id']] = new StudentDetail(
            user_id: $detail['user_id'],
            id: $newId,
            career_id: $detail['career_id'] ?? null,
            n_control: $detail['n_control'] ?? null,
            semestre: $detail['semestre'] ?? null,
            group: $detail['group'] ?? null,
            workshop: $detail['workshop'] ?? null
        );

        return true;
    }

    public function attachStudentDetail(CreateStudentDetailDTO $detail, ModelsUser $user): User
    {
        $userId = $detail->user_id ?? $user->id;

        if (!$userId) {
            throw new \InvalidArgumentException('No se puede determinar el user_id');
        }

        $newId = count($this->studentDetails) + 1;
        $studentDetail = new StudentDetail(
            user_id: $userId,
            id: $newId,
            career_id: $detail->career_id,
            n_control: $detail->n_control,
            semestre: $detail->semestre,
            group: $detail->group,
            workshop: $detail->workshop
        );

        $this->studentDetails[$studentDetail->user_id] = $studentDetail;

        // Crear y retornar un User simulado con el detalle adjunto
        $domainUser = $this->createMockUser($studentDetail->user_id, $studentDetail);

        // Simular la asignación del rol STUDENT
        if (method_exists($user, 'syncRoles')) {
            // En el stub solo registramos que se llamó
            $this->users[$studentDetail->user_id]['roles'] = ['student'];
        }

        return $domainUser;
    }

    private function createMockUser(int $userId, ?StudentDetail $detail): User
    {
        // Crear un User de dominio simulado
        $user = new \App\Core\Domain\Entities\User(
            curp: 'TEST' . $userId . 'ABCDEFGHIJ',
            name: 'Usuario ' . $userId,
            last_name: 'Test',
            email: 'usuario' . $userId . '@test.com',
            password: 'hashed_password',
            phone_number: '+5215512345678'
        );

        // Asignar ID usando Reflection ya que la propiedad es readonly en el constructor
        $reflection = new \ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, $userId);

        // Asignar studentDetail
        if ($detail) {
            $detailProperty = $reflection->getProperty('studentDetail');
            $detailProperty->setAccessible(true);
            $detailProperty->setValue($user, $detail);
        }

        return $user;
    }

    // Métodos auxiliares para testing
    public function getIncrementCount(): int
    {
        return $this->incrementCount;
    }

    public function getStudentDetailCount(): int
    {
        return count(array_filter($this->studentDetails, fn($detail) => $detail instanceof StudentDetail));
    }

    public function clear(): void
    {
        $this->studentDetails = [];
        $this->users = [];
        $this->incrementCount = 0;
        $this->seedInitialData();
    }
}
