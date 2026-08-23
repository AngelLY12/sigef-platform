<?php

namespace App\Core\Application\DTO\Response\Events\EmailEvent\Metadata;

use App\Core\Domain\ValueObjects\EmailEvent\ConceptCriticalAmountAlertEmailMetadata;

/**
 * @OA\Schema(
 *     schema="ConceptCriticalAmountAlertMetadataResponse",
 *     type="object",
 *     required={"conceptName","fullName","amount","threshold","exceededBy","action"},
 *     @OA\Property(
 *         property="conceptName",
 *         type="string",
 *         description="Nombre del concepto",
 *         example="Inscripción"
 *     ),
 *     @OA\Property(
 *         property="fullName",
 *         type="string",
 *         description="Nombre completo del usuario",
 *         example="Juan Carlos López"
 *     ),
 *     @OA\Property(
 *         property="amount",
 *         type="string",
 *         description="Monto actual",
 *         example="9500.00"
 *     ),
 *     @OA\Property(
 *         property="threshold",
 *         type="string",
 *         description="Monto establecido como umbral crítico",
 *         example="8000.00"
 *     ),
 *     @OA\Property(
 *         property="exceededBy",
 *         type="string",
 *         description="Monto por el cual se superó el umbral",
 *         example="1500.00"
 *     ),
 *     @OA\Property(
 *         property="action",
 *         type="string",
 *         description="Acción asociada a la alerta",
 *         example="notify_financial_staff"
 *     )
 * )
 */
final readonly class ConceptCriticalAmountAlertMetadataResponse implements EmailEventMetadataResponse
{
    public function __construct(
        public string $conceptName,
        public string $fullName,
        public string $amount,
        public string $threshold,
        public string $exceededBy,
        public string $action)
    {
    }

    public static function create(ConceptCriticalAmountAlertEmailMetadata $data): self
    {
        return new self(
            conceptName: $data->conceptName,
            fullName: $data->fullName,
            amount: $data->amount,
            threshold: $data->threshold,
            exceededBy: $data->exceededBy,
            action: $data->action
        );
    }

}
