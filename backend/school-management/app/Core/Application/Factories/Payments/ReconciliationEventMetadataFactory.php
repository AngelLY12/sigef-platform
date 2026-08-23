<?php

namespace App\Core\Application\Factories\Payments;

use App\Core\Domain\Enum\Events\ReconciliationOutcome;
use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationCorrectedData;
use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationEventMetadata;
use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationMatchedData;

class ReconciliationEventMetadataFactory
{
    public static function fromArray(ReconciliationOutcome $outcome, array $data): ReconciliationEventMetadata
    {
        return match ($outcome) {
            ReconciliationOutcome::CORRECTED =>
                ReconciliationCorrectedData::createFromArray($data),
            ReconciliationOutcome::MATCHED =>
                ReconciliationMatchedData::createFromArray($data),
            default => throw new \UnexpectedValueException(
                "No existe metadata definida para el outcome: {$outcome->value}"
            ),

        };

    }

}
