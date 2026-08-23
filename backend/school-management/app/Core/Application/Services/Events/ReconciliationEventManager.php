<?php

namespace App\Core\Application\Services\Events;

use App\Core\Application\Services\Events\Contracts\ReconciliationEventManagerInterface;
use App\Core\Domain\Entities\ReconciliationEvent;
use App\Core\Domain\Enum\Events\ReconciliationOutcome;
use App\Core\Domain\Enum\Events\Sources\ReconciliationSourceType;
use App\Core\Domain\Repositories\Command\Events\ReconciliationEventRepInterface;
use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationEventMetadata;

final class ReconciliationEventManager implements ReconciliationEventManagerInterface
{
    public function __construct(
        private ReconciliationEventRepInterface $repository,
    ) {}

    public function create(
        int $paymentId,
        ReconciliationSourceType $sourceType,
        string $sourceId,
    ): ReconciliationEvent {

        $event = ReconciliationEvent::create(
            paymentId: $paymentId,
            sourceType: $sourceType,
            sourceId: $sourceId,
        );

        return $this->repository->create($event);
    }

    public function complete(
        ReconciliationEvent $event,
        ReconciliationOutcome $outcome,
        ?ReconciliationEventMetadata $metadata = null,
    ): void {
        $event->complete(
            outcome: $outcome,
            metadata: $metadata,
        );

        $this->repository->save($event);
    }

    public function fail(
        ReconciliationEvent $event,
        string $error,
        ReconciliationOutcome $outcome,
        ?array $metadata = null,
    ): void {
        $event->fail(
            errorMessage: $error,
            outcome: $outcome,
        );

        $this->repository->save($event);
    }
}
