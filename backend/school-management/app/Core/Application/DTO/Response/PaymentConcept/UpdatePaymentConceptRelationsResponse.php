<?php

namespace App\Core\Application\DTO\Response\PaymentConcept;

/**
 * @OA\Schema(
 *     schema="UpdatePaymentConceptRelationsResponse",
 *     type="object",
 *     required={"status","metadata", "message", "updatedAt"},
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "pending"}, example="active"),
 *     @OA\Property(
 *         property="metadata",
 *         type="object",
 *         @OA\Property(property="concept_name", type="string", example="Cuota renscripción"),
 *         @OA\Property(property="global_status", type="boolean", example=false),
 *         @OA\Property(property="students_count", type="integer", example=5),
 *         @OA\Property(property="exception_count", type="integer", example=5),
 *         @OA\Property(property="career_count", type="integer", example=3),
 *         @OA\Property(property="semester_count", type="integer", example=2),
 *         @OA\Property(
 *          property="tags",
 *          type="array",
 *          @OA\Items(
 *                  type="object",
 *                  @OA\Property(property="tag_", type="string", example="no_student_details"),
 *              )
 *          ),
 *
 *     ),
 *     @OA\Property(property="message", type="string", example="Concepto actualizado: Ahora aplica a career. Se agregaron 2 careers"),
 *     @OA\Property(property="updatedAt", type="string", format="date-time", example="2024-01-12 14:45:00"),
 *     @OA\Property(
 *         property="changes",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="field", type="string", example="applies_to"),
 *             @OA\Property(property="old", type="string", example="students"),
 *             @OA\Property(property="new", type="string", example="career"),
 *             @OA\Property(property="type", type="string", example="applies_to_changed")
 *         )
 *     ),
 *     @OA\Property(
 *          property="affectedSummary",
 *          type="array",
 *          @OA\Items(
 *              type="object",
 *              @OA\Property(property="newlyAffectedCount", type="integer", example=50),
 *              @OA\Property(property="removedCount", type="integer", example=10),
 *              @OA\Property(property="keptCount", type="integer", example=0),
 *              @OA\Property(property="totalAffectedCount", type="integer", example=60),
 *              @OA\Property(property="previouslyAffectedCount", type="integer", example=20)
 *          )
 *      ),
 * )
 */
class UpdatePaymentConceptRelationsResponse
{
    public function __construct(
        public readonly string $status,
        public readonly array $metadata,
        public readonly string $message,
        public readonly string $updatedAt,
        public readonly array $changes = [],
        public readonly ?array $affectedSummary = []
    )
    {

    }

}
