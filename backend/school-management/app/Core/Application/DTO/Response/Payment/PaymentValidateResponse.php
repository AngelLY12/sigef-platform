<?php

namespace App\Core\Application\DTO\Response\Payment;

use App\Core\Application\DTO\Response\User\UserDataResponse;

/**
 * @OA\Schema(
 *     schema="PaymentValidateResponse",
 *     type="object",
 *     @OA\Property(property="student", ref="#/components/schemas/UserDataResponse", nullable=true),
 *     @OA\Property(property="payment", ref="#/components/schemas/PaymentDataResponse", nullable=true),
 *     @OA\Property(property="updatedAt", type="string", format="date-time", example="2024-01-12 14:45:00"),
 *     @OA\Property(
 *          property="metadata",
 *          type="object",
 *          @OA\Property(property="wasCreated", type="bool", example=true),
 *          @OA\Property(property="wasReconciled", type="bool", example=false),
 *          @OA\Property(property="message", type="string", example="Pago creado con exito"),
 *          @OA\Property(property="reconciliationResult", nullable=true ,type="object",
 *              @OA\Property(property="processed", type="integer", example=0),
 *              @OA\Property(property="updated", type="integer", example=0),
 *              @OA\Property(property="notified", type="integer", example=0),
 *              @OA\Property(property="failed", type="integer", example=0),
 *          ),
 *      ),
 * )
 */
class PaymentValidateResponse{

     public function __construct(
        public ?UserDataResponse $student,
        public ?PaymentDataResponse $payment,
        public readonly string $updatedAt,
        public readonly array $metadata
    ) {}

}
