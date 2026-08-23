<?php

namespace App\Core\Application\DTO\Response\Events\EmailEvent\Metadata;

use App\Core\Domain\ValueObjects\EmailEvent\ConceptCreatedEmailMetadata;

/**
 * @OA\Schema(
 *     schema="ConceptCreatedMetadataResponse",
 *     type="object",
 *     required={"recipientName","conceptName","amount","startDate"},
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
 *         description="Monto del concepto",
 *         example="1500.00"
 *     ),
 *     @OA\Property(
 *         property="endDate",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Fecha límite del concepto",
 *         example="2026-09-30T23:59:59Z"
 *     ),
 *     @OA\Property(
 *         property="startDate",
 *         type="string",
 *         format="date-time",
 *         description="Fecha de inicio del concepto",
 *         example="2026-08-20T00:00:00Z"
 *     )
 * )
 */
final readonly class ConceptCreatedMetadataResponse implements EmailEventMetadataResponse
{
    public function __construct(
        public string $recipientName,
        public string $conceptName,
        public string $amount,
        public ?string $endDate,
        public string $startDate,
    ){}

    public static function create(ConceptCreatedEmailMetadata $data): self
    {
        return new self(
            recipientName: $data->recipientName,
            conceptName: $data->conceptName,
            amount: $data->amount,
            endDate: $data->endDate ?? null,
            startDate: $data->startDate,
        );
    }

}
