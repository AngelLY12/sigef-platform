<?php

namespace App\Core\Application\DTO\Response\PaymentConcept;

/**
 * @OA\Schema(
 *     schema="CreatePaymentConceptResponse",
 *     type="object",
 *     required={"id", "conceptName", "status", "appliesTo", "amount", "startDate", "endDate", "affectedStudentsCount", "message", "createdAt"},
 *     @OA\Property(property="id", type="integer", example=123),
 *     @OA\Property(property="conceptName", type="string", example="Matrícula 2024"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "pending"}, example="active"),
 *     @OA\Property(property="appliesTo", type="string", enum={"career", "semester", "students", "career_semester", "tag", "all"}, example="career"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Pago de matrícula del semestre"),
 *     @OA\Property(property="amount", type="number", format="float", example=1500.00),
 *     @OA\Property(property="startDate", type="string", format="date", example="2024-01-15"),
 *     @OA\Property(property="endDate", nullable=true, type="string", format="date", example="2024-02-15"),
 *     @OA\Property(property="affectedStudentsCount", type="integer", example=245),
 *     @OA\Property(
 *         property="metadata",
 *         type="object",
 *         @OA\Property(property="exception_count", type="integer", example=5),
 *         @OA\Property(property="career_count", type="integer", example=3),
 *         @OA\Property(property="semester_count", type="integer", example=2)
 *     ),
 *     @OA\Property(property="message", type="string", example="Concepto creado exitosamente. Afecta a 245 estudiante(s)"),
 *     @OA\Property(property="createdAt", type="string", format="date-time", example="2024-01-10 10:30:00")
 * )
 */
class CreatePaymentConceptResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $conceptName,
        public readonly string $status,
        public readonly string $appliesTo,
        public readonly string $amount,
        public readonly string $startDate,
        public readonly ?string $endDate,
        public readonly int $affectedStudentsCount,
        public readonly string $message,
        public readonly string $createdAt,
        public readonly array $metadata = [],
        public readonly ?string $description = null,

    ){}

}
