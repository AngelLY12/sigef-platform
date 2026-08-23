<?php

namespace App\Core\Application\DTO\Response\Events\EmailEvent\Metadata;

use App\Core\Domain\ValueObjects\EmailEvent\PaymentFailedEmailMetadata;

/**
 * @OA\Schema(
 *     schema="PaymentFailedMetadataResponse",
 *     type="object",
 *     required={"recipientName","error","conceptName","amount","amountReceived"},
 *     @OA\Property(
 *         property="recipientName",
 *         type="string",
 *         description="Nombre del destinatario",
 *         example="Juan Carlos"
 *     ),
 *     @OA\Property(
 *         property="error",
 *         type="string",
 *         description="Descripción del error que provocó el fallo",
 *         example="Payment method was declined"
 *     ),
 *     @OA\Property(
 *         property="conceptName",
 *         type="string",
 *         description="Nombre del concepto de pago",
 *         example="Inscripción"
 *     ),
 *     @OA\Property(
 *         property="amount",
 *         type="string",
 *         description="Monto solicitado",
 *         example="1500.00"
 *     ),
 *     @OA\Property(
 *         property="amountReceived",
 *         type="string",
 *         description="Monto recibido al momento del fallo",
 *         example="0.00"
 *     )
 * )
 */
final readonly class PaymentFailedMetadataResponse implements EmailEventMetadataResponse
{
    public function __construct(
        public string $recipientName,
        public string $error,
        public string $conceptName,
        public string $amount,
        public string $amountReceived
    )
    {}

    public static function create(PaymentFailedEmailMetadata $data): self
    {
        return new self(
            recipientName: $data->recipientName,
            error: $data->error,
            conceptName: $data->conceptName,
            amount: $data->amount,
            amountReceived: $data->amountReceived
        );
    }

}
