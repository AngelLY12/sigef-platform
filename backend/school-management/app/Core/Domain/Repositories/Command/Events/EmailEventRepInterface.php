<?php

namespace App\Core\Domain\Repositories\Command\Events;


use App\Core\Domain\Entities\EmailEvent;

interface EmailEventRepInterface
{
    public function create(EmailEvent $event): EmailEvent;
    public function save(EmailEvent $event): void;
    public function update(int $emailEventId, array $fields): EmailEvent;
}
