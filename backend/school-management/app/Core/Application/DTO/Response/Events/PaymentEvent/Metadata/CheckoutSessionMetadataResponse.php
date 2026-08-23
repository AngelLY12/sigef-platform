<?php

namespace App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata;

use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session\CheckoutSessionAsyncCompletedData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session\CheckoutSessionCompletedData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session\CheckoutSessionExpiredData;

/**
 * @OA\Schema(
 *     schema="CheckoutSessionMetadataResponse",
 *     type="object",
 *     @OA\Property(
 *         property="conceptName",
 *         type="string",
 *         nullable=true,
 *         description="Nombre del concepto de pago",
 *         example="Inscripción"
 *     )
 * )
 */
final readonly class CheckoutSessionMetadataResponse implements PaymentEventMetadataResponse
{
    public function __construct(
        public ?string $conceptName,
    ){}

    public static function create(
        CheckoutSessionCompletedData
        |CheckoutSessionAsyncCompletedData
        |CheckoutSessionExpiredData $data
    ): self
    {
        return new self(
            conceptName: $data->stripeMetadata?->conceptName
        );
    }

}
