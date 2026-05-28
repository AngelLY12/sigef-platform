<?php

namespace App\Core\Application\DTO\Request\PaymentConcept;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Carbon\Carbon;


/**
 * @OA\Schema(
 *     schema="CreatePaymentConceptDTO",
 *     type="object",
 *     description="Datos para crear un concepto de pago",
 *     @OA\Property(property="concept_name", type="string", example="Pago de inscripción"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Pago correspondiente al semestre 2025A"),
 *     @OA\Property(property="amount", type="string", example="1500.00"),
 *     @OA\Property(property="status", ref="#/components/schemas/PaymentConceptStatus", example="activo"),
 *     @OA\Property(property="start_date", type="string", format="date", nullable=true, example="2025-09-01"),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true, example="2025-12-31"),
 *     @OA\Property(property="appliesTo", ref="#/components/schemas/PaymentConceptAppliesTo", example="todos"),
 *     @OA\Property(
 *         property="semesters",
 *         type="array",
 *         @OA\Items(type="integer"),
 *         nullable=true,
 *         example={1,2,3},
 *         description="Semestres asociados al concepto"
 *     ),
 *     @OA\Property(
 *         property="careers",
 *         type="array",
 *         @OA\Items(type="integer"),
 *         nullable=true,
 *         example={1,2},
 *         description="Carreras asociadas al concepto"
 *     ),
 *      @OA\Property(
 *         property="students",
 *         type="array",
 *         @OA\Items(type="string"),
 *         nullable=true,
 *         example={"12345","67891"},
 *         description="Numeros de control de estudiantes asociados al concepto"
 *      ),
 *     @OA\Property(
 *          property="exceptionStudents",
 *          type="array",
 *          @OA\Items(type="string"),
 *          nullable=true,
 *          example={"12345","67891"},
 *          description="Numeros de control de estudiantes a los que el concepto no aplica"
 *       ),
 *     @OA\Property(
 *           property="applicantTags",
 *           type="array",
 *           @OA\Items(type="string"),
 *           nullable=true,
 *           example={"no_student_details","applicants"},
 *           description="Array para aplicar conceptos a alumnos con casos especiales"
 *        ),
 * )
 */

class CreatePaymentConceptDTO {
    public function __construct(
        public string $concept_name,
        public string $amount,
        public PaymentConceptStatus $status,
        public PaymentConceptAppliesTo $appliesTo,
        public ?string $description = null,
        public ?Carbon $start_date = null,
        public ?Carbon $end_date = null,
        public array|int|null $semesters = null,
        public array|int|null $careers = null,
        public array|string|null $students = null,
        public array|string|null $exceptionStudents = null,
        public array|string|null $applicantTags = null,
    ) {}

    public function toArray(): array
    {
        return [
            "concept_name" => $this->concept_name,
            "amount" => $this->amount,
            "status" => $this->status->value,
            "applies_to" => $this->appliesTo->value,
            "description" => $this->description ?? null,
            "start_date" => $this->start_date ?? Carbon::now(),
            "end_date" => $this->end_date ?? null,
            "semesters" => $this->semesters ?? null,
            "careers" => $this->careers ?? null,
            "students" => $this->students ?? null,
            "exception_students" => $this->exceptionStudents ?? null,
            "applicant_tags" => $this->applicantTags ?? null,
        ];
    }

}
