<?php

namespace App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata;

use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentSucceededData;

/**
 * @OA\Schema(
 *     schema="PaymentIntentSucceededMetadataResponse",
 *     type="object",
 *     required={"latestCharge","intentStatus"},
 *     @OA\Property(
 *         property="latestCharge",
 *         type="string",
 *         description="Identificador del último Charge asociado al Payment Intent",
 *         example="ch_3U6JrCCDJnKApcPA0mOvSbDr"
 *     ),
 *     @OA\Property(
 *         property="intentStatus",
 *         type="string",
 *         description="Estado final del Payment Intent",
 *         example="succeeded"
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
final readonly class PaymentIntentSucceededMetadataResponse implements PaymentEventMetadataResponse
{
    public function __construct(
        public string $latestCharge,
        public string $intentStatus,
        public ?string $conceptName,
    ){}

    public static function create(PaymentIntentSucceededData $data): self
    {
        return new self(
            latestCharge: $data->latestCharge,
            intentStatus: $data->intentStatus,
            conceptName: $data->stripeMetadata?->conceptName
        );
    }

}
