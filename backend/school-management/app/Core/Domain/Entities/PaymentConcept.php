<?php

namespace App\Core\Domain\Entities;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="DomainPaymentConcept",
 *     type="object",
 *     description="Representa un concepto de pago",
 *     @OA\Property(property="concept_name", type="string", example="Pago de inscripción"),
 *     @OA\Property(property="status", ref="#/components/schemas/PaymentConceptStatus", example="activo"),
 *     @OA\Property(property="start_date", type="string", format="date", example="2025-09-01"),
 *     @OA\Property(property="amount", type="string", example="1500.00"),
 *     @OA\Property(property="applies_to", ref="#/components/schemas/PaymentConceptAppliesTo", example="todos"),
 *     @OA\Property(property="userIds", type="array", @OA\Items(type="integer"), example={1,2,3}),
 *     @OA\Property(property="careerIds", type="array", @OA\Items(type="integer"), example={1,2}),
 *     @OA\Property(property="semesters", type="array", @OA\Items(type="integer"), example={1,2,3}),
 *     @OA\Property(property="exceptionUserIds", type="array", @OA\Items(type="integer"), example={1,2,3}),
 *     @OA\Property(property="applicantTags", type="array", @OA\Items(ref="#/components/schemas/PaymentConceptApplicantType")),
 *     @OA\Property(property="id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="description", type="string", nullable=true, example="Pago correspondiente al semestre 2025A"),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true, example="2025-12-31"),
 * )
 */
class PaymentConcept
{
    public function __construct(
        public string $concept_name,
        /** @var PaymentConceptStatus */
        public PaymentConceptStatus $status,
        public Carbon $start_date,
        public string $amount,
        /** @var PaymentConceptAppliesTo */
        public PaymentConceptAppliesTo $applies_to,
        /** @var User[] */
        private array $userIds = [],
        /** @var Career[] */
        private array $careerIds = [],
        private array $semesters = [],
        private array $exceptionUserIds = [],
        /** @var PaymentConceptApplicantType[] */
        private array $applicantTags =[],
        public ?int $id=null,
        public ?string $description=null,
        public ?Carbon $end_date=null,
    ) {}

    public function toResponse(): array
    {
        return [
            'id' => $this->id,
            'concept_name' => $this->concept_name,
            'description'  => $this->description,
            'status'       => $this->status->value,
            'start_date'   => $this->start_date->toDateString(),
            'end_date'     => $this->end_date?->toDateString(),
            'amount'       => $this->amount,
            'applies_to'   => $this->applies_to->value,
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'concept_name' => $this->concept_name,
            'description'  => $this->description,
            'status'       => $this->status,
            'start_date'   => $this->start_date,
            'end_date'     => $this->end_date,
            'amount'       => $this->amount,
            'applies_to'   => $this->applies_to,
            'user_ids' => $this->getUserIds(),
            'career_ids' => $this->getCareerIds(),
            'semesters' => $this->getSemesters(),
            'exception_user_ids' => $this->getExceptionUsersIds(),
            'applicant_tags' => $this->getApplicantTag(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            concept_name: $data['concept_name'],
            status: $data['status'] instanceof PaymentConceptStatus
                ? $data['status']
                : PaymentConceptStatus::from($data['status']),
            start_date: $data['start_date'] instanceof Carbon
                ? $data['start_date']
                : Carbon::parse($data['start_date']),
            amount: $data['amount'],
            applies_to: $data['applies_to'] instanceof PaymentConceptAppliesTo
                ? $data['applies_to']
                : PaymentConceptAppliesTo::from($data['applies_to']),
            userIds: $data['user_ids'] ?? [],
            careerIds: $data['career_ids'] ?? [],
            semesters: $data['semesters'] ?? [],
            exceptionUserIds: $data['exception_user_ids'] ?? [],
            applicantTags: $data['applicant_tags'] ?? [],
            id: $data['id'] ?? null,
            description: $data['description'] ?? null,
            end_date: isset($data['end_date'])
                ? ($data['end_date'] instanceof Carbon
                    ? $data['end_date']
                    : Carbon::parse($data['end_date']))
                : null
        );
    }

    public function isActive(): bool
    {
        return $this->status === PaymentConceptStatus::ACTIVO;
    }
    public function isDisable(): bool
    {
        return $this->status === PaymentConceptStatus::DESACTIVADO;
    }

    public function isFinalize(): bool
    {
        return $this->status === PaymentConceptStatus::FINALIZADO;
    }

    public function isDelete(): bool
    {
        return $this->status === PaymentConceptStatus::ELIMINADO;
    }

     public function isExpired(): bool
    {
        $today = Carbon::today();
        if ($this->end_date && $today > $this->end_date) {
            return true;
        }
        return false;
    }

    public function isGlobal(): bool
    {
        return $this->applies_to === PaymentConceptAppliesTo::TODOS;
    }

    public function hasStarted(): bool
    {
        $today = Carbon::today();
        return $today >= $this->start_date;
    }

    public function hasUser(int $userId): bool
    {
        return in_array($userId, $this->userIds, true);
    }

    public function hasCareer(int $careerId): bool
    {
        return in_array($careerId, $this->careerIds, true);
    }

    public function hasSemester(int|string $semester): bool
    {
        return in_array((string) $semester, array_map('strval', $this->semesters), true);
    }

    public function hasExceptionForUser(int $userId): bool
    {
        return in_array($userId, $this->exceptionUserIds, true);
    }

    public function hasTag(PaymentConceptApplicantType|string $tag): bool
    {
        if (is_string($tag)) {
            $tag = PaymentConceptApplicantType::tryFrom($tag);
            if (!$tag) {
                return false;
            }
        }

        return in_array($tag, $this->applicantTags, true);
    }




    public function setUserIds(array $ids): void { $this->userIds = $ids; }
    public function getUserIds(): array { return $this->userIds; }

    public function setCareerIds(array $ids): void { $this->careerIds = $ids; }
    public function getCareerIds(): array { return $this->careerIds; }

    public function setSemesters(array $semesters): void { $this->semesters = $semesters; }
    public function getSemesters(): array { return $this->semesters; }

    public function setExceptionUsersIds(array $ids): void {$this->exceptionUserIds = $ids;}
    public function getExceptionUsersIds(): array {return $this->exceptionUserIds;}

    public function setApplicantTag(array $applicantTags): void { $this->applicantTags = $applicantTags; }
    public function getApplicantTag(): array { return $this->applicantTags; }

}
