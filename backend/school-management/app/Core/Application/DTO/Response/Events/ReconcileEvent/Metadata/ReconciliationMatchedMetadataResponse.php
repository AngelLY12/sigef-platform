<?php

namespace App\Core\Application\DTO\Response\Events\ReconcileEvent\Metadata;

use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationMatchedData;

/**
 * @OA\Schema(
 *     schema="ReconciliationMatchedMetadataResponse",
 *     type="object",
 *     required={"dataSource"},
 *     @OA\Property(
 *         property="dataSource",
 *         type="string",
 *         description="Origen del dato con el que se encontró coincidencia. Corresponde al label de ReconciliationDataSource.",
 *         example="Cargo"
 *     )
 * )
 */
class ReconciliationMatchedMetadataResponse implements ReconciliationEventMetadataResponse
{
    public function __construct(
        public string $dataSource,
    ) {}

    public static function create(ReconciliationMatchedData $data): self
    {
        return new self(
            dataSource: $data->dataSource->label(),
        );
    }

}
