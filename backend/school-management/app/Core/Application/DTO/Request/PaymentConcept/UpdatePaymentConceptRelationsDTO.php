<?php

namespace App\Core\Application\DTO\Request\PaymentConcept;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;

/**
 * @OA\Schema(
 *     schema="UpdatePaymentConceptRelationsDTO",
 *     type="object",
 *     description="Datos para actualizar un concepto de pago",
 *     required={"id"},
 *     @OA\Property(property="id", type="integer", example=1, description="ID del concepto a actualizar"),
 *
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
 *     @OA\Property(
 *         property="students",
 *         type="array",
 *         @OA\Items(type="string"),
 *         nullable=true,
 *         example={"12345","67891"},
 *         description="Numeros de control de estudiantes asociados al concepto"
 *     ),
 *     @OA\Property(
 *          property="exceptionStudents",
 *          type="array",
 *          @OA\Items(type="string"),
 *          nullable=true,
 *          example={"12345","67891"},
 *          description="Numeros de control de estudiantes a los que el concepto no aplica"
 *      ),
 *     @OA\Property(
 *            property="applicantTags",
 *            type="array",
 *            @OA\Items(type="string"),
 *            nullable=true,
 *            example={"no_student_details","applicants"},
 *            description="Array para aplicar conceptos a alumnos con casos especiales"
 *       ),
 *     @OA\Property(property="appliesTo", ref="#/components/schemas/PaymentConceptAppliesTo", nullable=true, example="todos", description="A quién aplica el concepto"),
 *     @OA\Property(property="replaceRelations", type="boolean", example=false, description="Si es true, reemplaza las relaciones existentes con las nuevas"),
 *     @OA\Property(property="replaceExceptions", type="boolean", example=false, description="Si es true, reemplaza los estudiantes a los que no aplica el concepto"),
 *     @OA\Property(property="removeAllExceptions", type="boolean", example=false, description="Si es true, elimina los estudiantes a los que no aplicaba el concepto"),
 * )
 */
class UpdatePaymentConceptRelationsDTO
{
    public function __construct(
        public int $id,
        public array|int|null $semesters = null,
        public array|int|null $careers = null,
        public array|string|null $students = null,
        public ?PaymentConceptAppliesTo $appliesTo=null,
        public ?bool $replaceRelations = null,
        public array|string|null $exceptionStudents = null,
        public ?bool $replaceExceptions = null,
        public ?bool $removeAllExceptions = null,
        public array|string|null $applicantTags = null,
    ){}

    public function toArray(): array
    {
        return [
            'applies_to' => $this->appliesTo
        ];
    }

    public function toArrayEntire(): array
    {
        return [
            'id' => $this->id,
            'semesters' => $this->semesters,
            'careers' => $this->careers,
            'students' => $this->students,
            'applies_to' => $this->appliesTo instanceof PaymentConceptAppliesTo
                ? $this->appliesTo->value
                : $this->appliesTo,
            'replace_relations' => $this->replaceRelations,
            'exception_students' => $this->exceptionStudents,
            'replace_exceptions' => $this->replaceExceptions,
            'remove_all_exceptions' => $this->removeAllExceptions,
            'applicant_tags' => $this->applicantTags,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            semesters: $data['semesters'] ?? null,
            careers: $data['careers'] ?? null,
            students: $data['students'] ?? null,
            appliesTo: isset($data['applies_to'])
                ? ($data['applies_to'] instanceof PaymentConceptAppliesTo
                    ? $data['applies_to']
                    : PaymentConceptAppliesTo::tryFrom($data['applies_to']))
                : null,
            replaceRelations: $data['replace_relations'] ?? false,
            exceptionStudents: $data['exception_students'] ?? null,
            replaceExceptions: $data['replace_exceptions'] ?? false,
            removeAllExceptions: $data['remove_all_exceptions'] ?? false,
            applicantTags: $data['applicant_tags'] ?? null,
        );
    }

}
