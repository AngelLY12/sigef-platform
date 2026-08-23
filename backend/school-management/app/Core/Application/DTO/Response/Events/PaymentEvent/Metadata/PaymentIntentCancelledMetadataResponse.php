<?php

namespace App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata;

use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentCancelledData;

/**
 * @OA\Schema(
 *     schema="PaymentIntentCancelledMetadataResponse",
 *     type="object",
 *     required={"cancellationReason"},
 *     @OA\Property(
 *         property="cancellationReason",
 *         type="string",
 *         description="Motivo por el que el Payment Intent fue cancelado",
 *         example="abandoned"
 *     ),
 *     @OA\Property(
 *         property="conceptName",
 *         type="string",
 *         nullable=true,
 *         description="Nombre del concepto de pago",
 *         example="Inscripción"
 *     )
 * )
 */
final readonly class PaymentIntentCancelledMetadataResponse implements PaymentEventMetadataResponse
{
    public function __construct(
        public string $cancellationReason,
        public ?string $conceptName,
    ){}

    public static function create(PaymentIntentCancelledData $data): self
    {
        return new self(
            cancellationReason: $data->cancellationReason,
            conceptName: $data->stripeMetadata?->conceptName
        );
    }

}
