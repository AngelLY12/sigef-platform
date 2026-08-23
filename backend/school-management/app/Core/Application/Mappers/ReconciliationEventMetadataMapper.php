<?php

namespace App\Core\Application\Mappers;

use App\Core\Application\DTO\Response\Events\ReconcileEvent\Metadata\ReconciliationCorrectedMetadataResponse;
use App\Core\Application\DTO\Response\Events\ReconcileEvent\Metadata\ReconciliationEventMetadataResponse;
use App\Core\Application\DTO\Response\Events\ReconcileEvent\Metadata\ReconciliationMatchedMetadataResponse;
use App\Core\Domain\Enum\Events\ReconciliationOutcome;
use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationCorrectedData;
use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationEventMetadata;
use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationMatchedData;

class ReconciliationEventMetadataMapper
{
    public static function toResponse(
        ReconciliationOutcome $outcome,
        ?ReconciliationEventMetadata $metadata
    ): ?ReconciliationEventMetadataResponse
    {
        if($metadata === null) {
            return null;
        }
        return match ($outcome) {
            ReconciliationOutcome::CORRECTED =>
                self::corrected($metadata),
            ReconciliationOutcome::MATCHED =>
                self::matched($metadata),
            default => throw new \UnexpectedValueException(
                "No existe metadata definida para el outcome: {$outcome->value}"
            ),

        };
    }

    private static function corrected(
        ReconciliationEventMetadata $metadata
    ): ReconciliationCorrectedMetadataResponse
    {
        if(!$metadata instanceof ReconciliationCorrectedData)
        {
            throw new \LogicException('Invalid metadata for corrected.');
        }
        return ReconciliationCorrectedMetadataResponse::create($metadata);
    }

    private static function matched(
        ReconciliationEventMetadata $metadata
    ): ReconciliationMatchedMetadataResponse
    {
        if(!$metadata instanceof ReconciliationMatchedData)
        {
            throw new \LogicException('Invalid metadata for matched.');
        }
        return ReconciliationMatchedMetadataResponse::create($metadata);
    }

}
