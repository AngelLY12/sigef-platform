<?php

namespace App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata;

use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentRequiresActionData;

/**
 * @OA\Schema(
 *     schema="PaymentIntentRequiresActionMetadataResponse",
 *     type="object",
 *     required={"type"},
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         description="Tipo de acción requerida para completar el Payment Intent",
 *         example="use_stripe_sdk"
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
final readonly class PaymentIntentRequiresActionMetadataResponse implements PaymentEventMetadataResponse
{
    public function __construct(
        public string $type,
        public ?string $conceptName,
    ){}

    public static function create(PaymentIntentRequiresActionData $data): self
    {
        return new self(
            type: $data->requiredAction?->type,
            conceptName: $data->stripeMetadata?->conceptName
        );
    }

}
