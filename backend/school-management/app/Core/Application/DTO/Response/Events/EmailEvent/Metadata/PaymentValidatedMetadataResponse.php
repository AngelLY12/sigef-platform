<?php

namespace App\Core\Application\DTO\Response\Events\EmailEvent\Metadata;

use App\Core\Domain\ValueObjects\EmailEvent\PaymentValidatedEmailMetadata;

/**
 * @OA\Schema(
 *     schema="PaymentValidatedMetadataResponse",
 *     type="object",
 *     required={"recipientName","conceptName","amount","amountReceived","status"},
 *     @OA\Property(
 *         property="recipientName",
 *         type="string",
 *         description="Nombre del destinatario",
 *         example="Juan Carlos"
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
 *         description="Monto del pago",
 *         example="1500.00"
 *     ),
 *     @OA\Property(
 *         property="amountReceived",
 *         type="string",
 *         description="Monto recibido",
 *         example="1500.00"
 *     ),
 *     @OA\Property(
 *         property="paymentMethodType",
 *         type="string",
 *         nullable=true,
 *         description="Tipo de método de pago utilizado",
 *         example="card"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         description="Estado del pago",
 *         example="succeeded"
 *     ),
 *     @OA\Property(
 *         property="url",
 *         type="string",
 *         format="uri",
 *         nullable=true,
 *         description="URL asociada al pago",
 *         example="https://example.com/payment"
 *     )
 * )
 */
final readonly class PaymentValidatedMetadataResponse implements EmailEventMetadataResponse
{
    public function __construct(
        public string                $recipientName,
        public string                $conceptName,
        public string                $amount,
        public string                $amountReceived,
        public ?string $paymentMethodType,
        public string                $status,
        public ?string               $url
    )
    {
    }

    public static function create(PaymentValidatedEmailMetadata $data): self
    {
        return new self(
            recipientName: $data->recipientName,
            conceptName: $data->conceptName,
            amount: $data->amount,
            amountReceived: $data->amountReceived,
            paymentMethodType: $data->paymentMethodDetail?->type(),
            status: $data->status,
            url: $data->url ?? null
        );
    }

}
