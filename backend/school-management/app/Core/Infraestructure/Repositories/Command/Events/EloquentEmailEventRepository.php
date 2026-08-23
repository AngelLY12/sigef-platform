<?php

namespace App\Core\Infraestructure\Repositories\Command\Events;

use App\Core\Domain\Entities\EmailEvent;
use App\Core\Domain\Repositories\Command\Events\EmailEventRepInterface;
use App\Core\Infraestructure\Mappers\EmailEventMapper;
use App\Models\EmailEvent as EloquentEmailEvent;

class EloquentEmailEventRepository implements EmailEventRepInterface
{
    public function create(EmailEvent $event): EmailEvent
    {
        $eloquent = EloquentEmailEvent::create(
            EmailEventMapper::toPersistence($event)
        );

        return EmailEventMapper::toDomain($eloquent);
    }
    public function save(EmailEvent $event): void
    {

        if ($event->id === null) {
            throw new \InvalidArgumentException(
                'Cannot save a EmailEvent without an ID.'
            );
        }

        EloquentEmailEvent::findOrFail($event->id)
            ->update(EmailEventMapper::toPersistence($event));
    }

    public function update(int $emailEventId, array $fields): EmailEvent
    {
        $emailEvent = EloquentEmailEvent::findOrFail($emailEventId);
        $emailEvent->update($fields);
        $emailEvent->refresh();
        return EmailEventMapper::toDomain($emailEvent);
    }

}
