<?php

namespace App\Core\Application\DTO\Response\Events\ReconcileEvent\Metadata;

use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationCorrectedData;

/**
 * @OA\Schema(
 *     schema="ReconciliationCorrectedMetadataResponse",
 *     type="object",
 *     required={"dataSource","amountReceived"},
 *     @OA\Property(
 *         property="dataSource",
 *         type="string",
 *         description="Origen del dato utilizado para corregir la reconciliación. Corresponde al label de ReconciliationDataSource.",
 *         example="Cargo"
 *     ),
 *     @OA\Property(
 *         property="amountReceived",
 *         type="string",
 *         description="Monto recibido identificado durante la reconciliación",
 *         example="1500.00"
 *     ),
 *     @OA\Property(
 *         property="paymentIntentId",
 *         type="string",
 *         nullable=true,
 *         description="Identificador del Payment Intent de Stripe utilizado durante la reconciliación",
 *         example="pi_3U6JrCCDJnKApcPA0Sntz2yl"
 *     ),
 *     @OA\Property(
 *         property="chargeId",
 *         type="string",
 *         nullable=true,
 *         description="Identificador del Charge de Stripe utilizado durante la reconciliación",
 *         example="ch_3U6JrCCDJnKApcPA0mOvSbDr"
 *     )
 * )
 */
class ReconciliationCorrectedMetadataResponse implements ReconciliationEventMetadataResponse
{
    public function __construct(
        public string $dataSource,
        public string                   $amountReceived,
        public ?string                  $paymentIntentId = null,
        public ?string                  $chargeId = null,
    )
    {
    }

    public static function create(ReconciliationCorrectedData $data): self
    {
        return new self(
            dataSource: $data->dataSource->label(),
            amountReceived: $data->amountReceived,
            paymentIntentId: $data->paymentIntentId ?? null,
            chargeId: $data->chargeId ?? null,
        );
    }


}
