<?php
namespace App\Core\Application\DTO\Response\PaymentConcept;

/**
 * @OA\Schema(
 *     schema="PendingSummaryResponse",
 *     type="object",
 *     @OA\Property(property="totalAmount", type="string", nullable=true, description="Monto total pendiente", example="4500.00"),
 *     @OA\Property(property="totalCount", type="integer", nullable=true, description="Cantidad total de conceptos pendientes", example=3)
 * )
 */
class PendingSummaryResponse {
    public function __construct(
        public readonly ?string $totalAmount,
        public readonly ?int $totalCount
    ) {}
}

