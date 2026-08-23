<?php

namespace App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata;

use App\Core\Domain\Utils\Helpers\Money;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Charge\ChargeSucceededData;

/**
 * @OA\Schema(
 *     schema="ChargeSucceededMetadataResponse",
 *     type="object",
 *     required={"chargeId","amountCaptured","amountRefunded","paymentMethodType"},
 *     @OA\Property(
 *         property="chargeId",
 *         type="string",
 *         description="Identificador del Charge en Stripe",
 *         example="ch_3U6JrCCDJnKApcPA0mOvSbDr"
 *     ),
 *     @OA\Property(
 *         property="amountCaptured",
 *         type="string",
 *         description="Monto capturado por Stripe",
 *         example="1500.00"
 *     ),
 *     @OA\Property(
 *         property="amountRefunded",
 *         type="string",
 *         description="Monto reembolsado",
 *         example="0.00"
 *     ),
 *     @OA\Property(
 *         property="paymentMethodType",
 *         type="string",
 *         description="Tipo de método de pago utilizado",
 *         example="card"
 *     ),
 *     @OA\Property(
 *         property="receiptUrl",
 *         type="string",
 *         format="uri",
 *         nullable=true,
 *         description="URL del comprobante de pago de Stripe",
 *         example="https://pay.stripe.com/receipts/payment/..."
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
final readonly class ChargeSucceededMetadataResponse implements PaymentEventMetadataResponse
{
    public function __construct(
        public string  $chargeId,
        public string     $amountCaptured,
        public string     $amountRefunded,
        public string  $paymentMethodType,
        public ?string $receiptUrl,
        public ?string $conceptName,
    )
    {
    }

    public static function create(ChargeSucceededData $data): self
    {
        return new self(
            chargeId: $data->chargeId,
            amountCaptured: Money::from($data->amountCaptured)->divide('100')->finalize(),
            amountRefunded: Money::from($data->amountRefunded)->divide('100')->finalize(),
            paymentMethodType: $data->paymentMethodDetails?->type() ?? 'Desconocido',
            receiptUrl: $data->receiptUrl,
            conceptName: $data->stripeMetadata?->conceptName
        );
    }

}
