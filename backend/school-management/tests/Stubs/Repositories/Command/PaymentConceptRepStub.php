<?php

namespace Tests\Stubs\Repositories\Command;
use App\Core\Domain\Repositories\Command\Payments\PaymentConceptRepInterface;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentConceptRepStub implements PaymentConceptRepInterface
{
    private bool $throwDatabaseError = false;
    private array $concepts = [];
    private int $nextId = 1;

    public function __construct()
    {
        $this->initializeTestData();
    }

    private function initializeTestData(): void
    {
        // Conceptos de prueba iniciales
        $this->concepts = [
            1 => PaymentConcept::fromArray([
                'id' => 1,
                'concept_name' => 'Matrícula Semestral',
                'status' => PaymentConceptStatus::ACTIVO,
                'start_date' => Carbon::now(),
                'amount' => '5000.00',
                'applies_to' => 'todos',
                'description' => 'Pago de matrícula',
                'user_ids' => [1, 2, 3],
                'career_ids' => [101],
                'semesters' => [1, 2],
                'exception_user_ids' => [10],
                'applicant_tags' => ['nuevo']
            ]),
            2 => PaymentConcept::fromArray([
                'id' => 2,
                'concept_name' => 'Inscripción',
                'status' => PaymentConceptStatus::FINALIZADO,
                'start_date' => Carbon::now()->subMonths(2),
                'amount' => '2000.00',
                'applies_to' => 'carrera',
                'end_date' => Carbon::now()->subMonth(),
                'career_ids' => [101, 102]
            ]),
            3 => PaymentConcept::fromArray([
                'id' => 3,
                'concept_name' => 'Concepto Eliminado',
                'status' => PaymentConceptStatus::ELIMINADO,
                'start_date' => Carbon::now()->subDays(40), // Viejo
                'amount' => '1000.00',
                'applies_to' => 'todos'
            ]),
        ];
        $this->nextId = 4;
    }

    public function create(PaymentConcept $concept): PaymentConcept
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        $id = $concept->id ?? $this->nextId++;

        // Crear array de datos para fromArray
        $conceptData = $concept->toArray();
        $conceptData['id'] = $id;

        $newConcept = PaymentConcept::fromArray($conceptData);
        $this->concepts[$id] = $newConcept;

        return $newConcept;
    }

    public function update(int $conceptId, array $data): PaymentConcept
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (!isset($this->concepts[$conceptId])) {
            throw new ModelNotFoundException('Payment concept not found');
        }

        $existingConcept = $this->concepts[$conceptId];
        $existingData = $existingConcept->toArray();

        // Actualizar datos
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $existingData)) {
                $existingData[$key] = $value;
            }
        }

        // Si se actualiza el status, manejarlo como enum
        if (isset($data['status'])) {
            $existingData['status'] = $data['status'] instanceof PaymentConceptStatus
                ? $data['status']
                : PaymentConceptStatus::from($data['status']);
        }

        $updatedConcept = PaymentConcept::fromArray($existingData);
        $this->concepts[$conceptId] = $updatedConcept;

        return $updatedConcept;
    }

    public function deleteLogical(PaymentConcept $concept): PaymentConcept
    {
        return $this->update($concept->id, ['status' => PaymentConceptStatus::ELIMINADO]);
    }

    public function delete(int $conceptId): void
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (!isset($this->concepts[$conceptId])) {
            throw new ModelNotFoundException('Payment concept not found');
        }

        unset($this->concepts[$conceptId]);
    }

    public function attachToUsers(int $conceptId, UserIdListDTO $userIds, bool $replaceRelations = false): PaymentConcept
    {
        if (!isset($this->concepts[$conceptId])) {
            throw new ModelNotFoundException('Payment concept not found');
        }

        $existingConcept = $this->concepts[$conceptId];
        $existingData = $existingConcept->toArray();

        if ($replaceRelations) {
            $existingData['user_ids'] = $userIds->userIds ?? [];
        } else {
            $existingData['user_ids'] = array_unique(array_merge(
                $existingData['user_ids'],
                $userIds->userIds ?? []
            ));
        }

        $updatedConcept = PaymentConcept::fromArray($existingData);
        $this->concepts[$conceptId] = $updatedConcept;

        return $updatedConcept;
    }

    public function attachToCareer(int $conceptId, array $careerIds, bool $replaceRelations = false): PaymentConcept
    {
        if (!isset($this->concepts[$conceptId])) {
            throw new ModelNotFoundException('Payment concept not found');
        }

        $existingConcept = $this->concepts[$conceptId];
        $existingData = $existingConcept->toArray();

        if ($replaceRelations) {
            $existingData['career_ids'] = $careerIds;
        } else {
            $existingData['career_ids'] = array_unique(array_merge(
                $existingData['career_ids'],
                $careerIds
            ));
        }

        $updatedConcept = PaymentConcept::fromArray($existingData);
        $this->concepts[$conceptId] = $updatedConcept;

        return $updatedConcept;
    }

    public function attachToSemester(int $conceptId, array $semesters, bool $replaceRelations = false): PaymentConcept
    {
        if (!isset($this->concepts[$conceptId])) {
            throw new ModelNotFoundException('Payment concept not found');
        }

        $existingConcept = $this->concepts[$conceptId];
        $existingData = $existingConcept->toArray();

        if ($replaceRelations) {
            $existingData['semesters'] = $semesters;
        } else {
            $existingData['semesters'] = array_unique(array_merge(
                $existingData['semesters'],
                $semesters
            ));
        }

        $updatedConcept = PaymentConcept::fromArray($existingData);
        $this->concepts[$conceptId] = $updatedConcept;

        return $updatedConcept;
    }

    public function attachToExceptionStudents(int $conceptId, UserIdListDTO $userIds, bool $replaceRelations = false): PaymentConcept
    {
        if (!isset($this->concepts[$conceptId])) {
            throw new ModelNotFoundException('Payment concept not found');
        }

        $existingConcept = $this->concepts[$conceptId];
        $existingData = $existingConcept->toArray();

        if ($replaceRelations) {
            $existingData['exception_user_ids'] = $userIds->userIds ?? [];
        } else {
            $existingData['exception_user_ids'] = array_unique(array_merge(
                $existingData['exception_user_ids'],
                $userIds->userIds ?? []
            ));
        }

        $updatedConcept = PaymentConcept::fromArray($existingData);
        $this->concepts[$conceptId] = $updatedConcept;

        return $updatedConcept;
    }

    public function attachToApplicantTag(int $conceptId, array $tags, bool $replaceRelations = false): PaymentConcept
    {
        if (!isset($this->concepts[$conceptId])) {
            throw new ModelNotFoundException('Payment concept not found');
        }

        $existingConcept = $this->concepts[$conceptId];
        $existingData = $existingConcept->toArray();

        if ($replaceRelations) {
            $existingData['applicant_tags'] = $tags;
        } else {
            $existingData['applicant_tags'] = array_unique(array_merge(
                $existingData['applicant_tags'],
                $tags
            ));
        }

        $updatedConcept = PaymentConcept::fromArray($existingData);
        $this->concepts[$conceptId] = $updatedConcept;

        return $updatedConcept;
    }

    public function detachFromCareer(int $conceptId): void
    {
        if (!isset($this->concepts[$conceptId])) {
            throw new ModelNotFoundException('Payment concept not found');
        }

        $existingConcept = $this->concepts[$conceptId];
        $existingData = $existingConcept->toArray();
        $existingData['career_ids'] = [];

        $this->concepts[$conceptId] = PaymentConcept::fromArray($existingData);
    }

    public function detachFromSemester(int $conceptId): void
    {
        if (!isset($this->concepts[$conceptId])) {
            throw new ModelNotFoundException('Payment concept not found');
        }

        $existingConcept = $this->concepts[$conceptId];
        $existingData = $existingConcept->toArray();
        $existingData['semesters'] = [];

        $this->concepts[$conceptId] = PaymentConcept::fromArray($existingData);
    }

    public function detachFromUsers(int $conceptId): void
    {
        if (!isset($this->concepts[$conceptId])) {
            throw new ModelNotFoundException('Payment concept not found');
        }

        $existingConcept = $this->concepts[$conceptId];
        $existingData = $existingConcept->toArray();
        $existingData['user_ids'] = [];

        $this->concepts[$conceptId] = PaymentConcept::fromArray($existingData);
    }

    public function detachFromExceptionStudents(int $conceptId): void
    {
        if (!isset($this->concepts[$conceptId])) {
            throw new ModelNotFoundException('Payment concept not found');
        }

        $existingConcept = $this->concepts[$conceptId];
        $existingData = $existingConcept->toArray();
        $existingData['exception_user_ids'] = [];

        $this->concepts[$conceptId] = PaymentConcept::fromArray($existingData);
    }

    public function detachFromApplicantTag(int $conceptId): void
    {
        if (!isset($this->concepts[$conceptId])) {
            throw new ModelNotFoundException('Payment concept not found');
        }

        $existingConcept = $this->concepts[$conceptId];
        $existingData = $existingConcept->toArray();
        $existingData['applicant_tags'] = [];

        $this->concepts[$conceptId] = PaymentConcept::fromArray($existingData);
    }

    public function finalize(PaymentConcept $concept): PaymentConcept
    {
        return $this->update($concept->id, [
            'status' => PaymentConceptStatus::FINALIZADO,
            'end_date' => Carbon::now()
        ]);
    }

    public function disable(PaymentConcept $concept): PaymentConcept
    {
        return $this->update($concept->id, ['status' => PaymentConceptStatus::DESACTIVADO]);
    }

    public function activate(PaymentConcept $concept): PaymentConcept
    {
        return $this->update($concept->id, [
            'status' => PaymentConceptStatus::ACTIVO,
            'end_date' => null
        ]);
    }

    public function cleanDeletedConcepts(): int
    {
        $deletedCount = 0;
        $thresholdDate = Carbon::now()->subDays(30);

        foreach ($this->concepts as $id => $concept) {
            if ($concept->status === PaymentConceptStatus::ELIMINADO) {
                // Simular eliminación de conceptos viejos
                $deletedCount++;
                unset($this->concepts[$id]);
            }
        }

        return $deletedCount;
    }

    public function finalizePaymentConcepts(): array
    {
        $updatedConcepts = [];
        $today = Carbon::today();

        foreach ($this->concepts as $concept) {
            if ($concept->status === PaymentConceptStatus::ACTIVO &&
                $concept->end_date &&
                $concept->end_date < $today) {

                $oldStatus = $concept->status->value;
                $this->update($concept->id, ['status' => PaymentConceptStatus::FINALIZADO]);

                $updatedConcepts[] = [
                    'id' => $concept->id,
                    'old_status' => $oldStatus,
                    'new_status' => PaymentConceptStatus::FINALIZADO->value
                ];
            }
        }

        return $updatedConcepts;
    }

    // Métodos de configuración para pruebas

    public function shouldThrowDatabaseError(bool $throw = true): self
    {
        $this->throwDatabaseError = $throw;
        return $this;
    }

    public function addConcept(PaymentConcept $concept): self
    {
        $id = $concept->id ?? $this->nextId++;

        if ($concept->id === null) {
            $conceptData = $concept->toArray();
            $conceptData['id'] = $id;
            $conceptWithId = PaymentConcept::fromArray($conceptData);
            $this->concepts[$id] = $conceptWithId;
        } else {
            $this->concepts[$id] = $concept;
            if ($id >= $this->nextId) {
                $this->nextId = $id + 1;
            }
        }

        return $this;
    }

    public function getConcept(int $id): ?PaymentConcept
    {
        return $this->concepts[$id] ?? null;
    }

    public function getConceptsCount(): int
    {
        return count($this->concepts);
    }

    public function clearConcepts(): self
    {
        $this->concepts = [];
        $this->nextId = 1;
        return $this;
    }

    public function getAllConcepts(): array
    {
        return $this->concepts;
    }
}
