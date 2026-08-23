<?php

namespace App\Core\Application\DTO\Response\Events\EmailEvent\Metadata;

use App\Core\Domain\ValueObjects\EmailEvent\PaymentRequiresActionEmailMetadata;

/**
 * @OA\Schema(
 *     schema="PaymentRequiresActionMetadataResponse",
 *     type="object",
 *     required={"conceptName","amount","recipientName"},
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
 *         property="recipientName",
 *         type="string",
 *         description="Nombre del destinatario",
 *         example="Juan Carlos"
 *     ),
 *     @OA\Property(
 *         property="actionType",
 *         type="string",
 *         nullable=true,
 *         description="Tipo de acción requerida para continuar el pago",
 *         example="customer_action"
 *     )
 * )
 */
final readonly class PaymentRequiresActionMetadataResponse implements EmailEventMetadataResponse
{
    public function __construct(
        public string $conceptName,
        public string $amount,
        public string $recipientName,
        public ?string $actionType,
    )
    {
    }

    public static function create(PaymentRequiresActionEmailMetadata $data): self
    {
        return new self(
            conceptName: $data->conceptName,
            amount: $data->amount,
            recipientName: $data->recipientName,
            actionType: $data->requiredActionDetails->type ?? null,
        );
    }

}
