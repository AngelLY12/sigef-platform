<?php

namespace App\Core\Domain\Repositories\Command\Events;

use App\Core\Domain\Entities\ReconciliationEvent;

interface ReconciliationEventRepInterface
{
    public function create(ReconciliationEvent $event): ReconciliationEvent;
    public function save(ReconciliationEvent $event): void;
    public function update(int $reconciliationEventId, array $fields): ReconciliationEvent;
}
