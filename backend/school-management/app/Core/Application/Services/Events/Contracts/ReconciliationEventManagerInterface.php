<?php

namespace App\Core\Application\Services\Events\Contracts;


use App\Core\Domain\Entities\ReconciliationEvent;
use App\Core\Domain\Enum\Events\ReconciliationOutcome;
use App\Core\Domain\Enum\Events\Sources\ReconciliationSourceType;
use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationEventMetadata;

interface ReconciliationEventManagerInterface
{
    public function create(
        int $paymentId,
        ReconciliationSourceType $sourceType,
        string $sourceId,
    ): ReconciliationEvent;

    public function complete(
        ReconciliationEvent $event,
        ReconciliationOutcome $outcome,
        ?ReconciliationEventMetadata $metadata = null,
    ): void;

    public function fail(
        ReconciliationEvent $event,
        string $error,
        ReconciliationOutcome $outcome,
    ): void;


}
