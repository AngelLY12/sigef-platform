<?php

namespace App\Core\Application\DTO\Response\Events\EmailEvent\Metadata;

use App\Core\Domain\ValueObjects\EmailEvent\PaymentCreatedEmailMetadata;

/**
 * @OA\Schema(
 *     schema="PaymentCreatedMetadataResponse",
 *     type="object",
 *     required={"recipientName","conceptName","amount"},
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
 *         property="createdAt",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Fecha de creación del pago",
 *         example="2026-08-20T10:30:00Z"
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
final readonly class PaymentCreatedMetadataResponse implements EmailEventMetadataResponse
{
    public function __construct(
        public string $recipientName,
        public string $conceptName,
        public string $amount,
        public ?string $createdAt,
        public ?string $url
    ) {}

    public static function create(PaymentCreatedEmailMetadata $data): self
    {
        return new self(
            recipientName: $data->recipientName,
            conceptName: $data->conceptName,
            amount: $data->amount,
            createdAt: $data->createdAt ?? null,
            url: $data->url ?? null
        );
    }

}
