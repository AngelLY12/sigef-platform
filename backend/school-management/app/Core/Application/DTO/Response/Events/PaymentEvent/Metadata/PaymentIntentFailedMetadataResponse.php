<?php

namespace App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata;

use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentFailedData;

/**
 * @OA\Schema(
 *     schema="PaymentIntentFailedMetadataResponse",
 *     type="object",
 *     required={"latestCharge"},
 *     @OA\Property(
 *         property="errorCode",
 *         type="string",
 *         nullable=true,
 *         description="Código del error del método de pago",
 *         example="card_declined"
 *     ),
 *     @OA\Property(
 *         property="declineCode",
 *         type="string",
 *         nullable=true,
 *         description="Código específico de rechazo proporcionado por Stripe",
 *         example="insufficient_funds"
 *     ),
 *     @OA\Property(
 *         property="errorMessage",
 *         type="string",
 *         nullable=true,
 *         description="Mensaje descriptivo del error",
 *         example="Your card has insufficient funds."
 *     ),
 *     @OA\Property(
 *         property="errorType",
 *         type="string",
 *         nullable=true,
 *         description="Tipo del error reportado por Stripe",
 *         example="card_error"
 *     ),
 *     @OA\Property(
 *         property="latestCharge",
 *         type="string",
 *         description="Identificador del último Charge asociado al Payment Intent",
 *         example="ch_3U6JrCCDJnKApcPA0mOvSbDr"
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
final readonly class PaymentIntentFailedMetadataResponse implements PaymentEventMetadataResponse
{
    public function __construct(
        public ?string $errorCode,
        public ?string $declineCode,
        public ?string $errorMessage,
        public ?string $errorType,
        public string $latestCharge,
        public ?string $conceptName,
    ){}

    public static function create(PaymentIntentFailedData $data): self
    {
        return new self(
            errorCode: $data->stripeErrorCode ?? null,
            declineCode: $data->stripeDeclineCode ?? null,
            errorMessage: $data->stripeErrorMessage ?? null,
            errorType: $data->stripeErrorType ?? null,
            latestCharge: $data->latestCharge,
            conceptName: $data->stripeMetadata?->conceptName
        );
    }

}
