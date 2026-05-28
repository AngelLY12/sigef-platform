<?php

namespace App\Core\Application\DTO\Response\Payment;

/**
 * @OA\Schema(
 *     schema="PaymentsSummaryResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="totalPayments",
 *         type="string",
 *         description="Monto total de pagos realizados por el alumno",
 *         example="25000.00"
 *     ),
 *
 *     @OA\Property(
 *          property="paymentsByMonth",
 *          type="object",
 *          description="Pagos realizados por el alumno agrupados por mes (YYYY-MM)",
 *          additionalProperties=@OA\Property (
 *              type="string",
 *              example="15000.00"
 *          ),
 *          example={
 *              "2024-01":"15000.00",
 *              "2024-02":"12000.00",
 *              "2024-03":"18000.00"
 *          }
 *      )
 * )
 */
class PaymentsSummaryResponse
{
    public function __construct(
        public readonly string $totalPayments,
        public readonly array $paymentsByMonth
    ){}

}
