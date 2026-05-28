<?php

namespace App\Core\Application\DTO\Response\PaymentConcept;

/**
 * @OA\Schema(
 *     schema="ConceptRelationsToDisplay",
 *     type="object",
 *     description="Representa las relaciones de un concepto de pago",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="concept_name", type="string", example="Pago de inscripción"),
 *     @OA\Property(property="applies_to", ref="#/components/schemas/PaymentConceptAppliesTo", example="todos"),
 *     @OA\Property(property="users", type="array", @OA\Items(type="string"), example={"12324","2435646","3323232"}),
 *     @OA\Property(property="careers", type="array", @OA\Items(type="integer"), example={1,2,3}),
 *     @OA\Property(property="semesters", type="array", @OA\Items(type="integer"), example={1,2,3}),
 *     @OA\Property(property="exceptionUsers", type="array", @OA\Items(type="string"), example={"12326","24646","33232"}),
 *     @OA\Property(property="applicantTags", type="array", @OA\Items(ref="#/components/schemas/PaymentConceptApplicantType")),
 * )
 */
class ConceptRelationsToDisplay
{

    public function __construct(
        public readonly int $id,
        public readonly string $concept_name,
        public readonly string $applies_to,
        public readonly array $users = [],
        public readonly array $careers = [],
        public readonly array $semesters = [],
        public readonly array $exceptionUsers = [],
        public readonly array $applicantTags =[],
    ){}

}
