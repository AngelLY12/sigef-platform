<?php

namespace App\Core\Infraestructure\Repositories\Command\Events;

use App\Core\Domain\Entities\ReconciliationEvent;
use App\Core\Domain\Repositories\Command\Events\ReconciliationEventRepInterface;
use App\Core\Infraestructure\Mappers\ReconciliationEventMapper;
use App\Models\PaymentReconciliationEvent as EloquentReconciliationEvent;

class EloquentReconciliationEventRepository implements ReconciliationEventRepInterface
{
    public function create(ReconciliationEvent $event): ReconciliationEvent
    {
        $eloquent = EloquentReconciliationEvent::create(
            ReconciliationEventMapper::toPersistence($event)
        );

        return ReconciliationEventMapper::toDomain($eloquent);
    }

    public function save(ReconciliationEvent $event): void
    {
        if ($event->id === null) {
            throw new \InvalidArgumentException(
                'Cannot save a ReconciliationEvent without an ID.'
            );
        }
        EloquentReconciliationEvent::query()
            ->whereKey($event->id)
            ->update(ReconciliationEventMapper::toPersistence($event));
    }


    public function update(int $reconciliationEventId, array $fields): ReconciliationEvent
    {
        $reconciliationEvent = EloquentReconciliationEvent::findOrFail($reconciliationEventId);
        $reconciliationEvent->update($fields);
        $reconciliationEvent->refresh();
        return ReconciliationEventMapper::toDomain($reconciliationEvent);
    }

}
