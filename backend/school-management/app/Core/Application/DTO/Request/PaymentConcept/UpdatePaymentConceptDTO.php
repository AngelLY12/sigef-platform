<?php

namespace App\Core\Application\DTO\Request\PaymentConcept;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="UpdatePaymentConceptDTO",
 *     type="object",
 *     description="Datos para actualizar un concepto de pago",
 *     required={"id","fieldsToUpdate"},
 *     @OA\Property(property="id", type="integer", example=1, description="ID del concepto a actualizar"),
 *     @OA\Property(property="concept_name", type="string", example="Pago de inscripción"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Pago correspondiente al semestre 2025A"),
 *     @OA\Property(property="amount", type="string", example="1500.00"),
 *     @OA\Property(property="start_date", type="string", format="date", nullable=true, example="2025-09-01"),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true, example="2025-12-31"),
 * )
 */
class UpdatePaymentConceptDTO
{
    public function __construct(
        public int $id,
        public ?string $concept_name = null,
        public ?string $description = null,
        public ?Carbon $start_date = null,
        public ?Carbon $end_date = null,
        public ?string $amount = null,

    ) {}

    public function toArray(): array
    {
        return [
            'concept_name' => $this->concept_name,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'amount' => $this->amount,
        ];
    }
}
