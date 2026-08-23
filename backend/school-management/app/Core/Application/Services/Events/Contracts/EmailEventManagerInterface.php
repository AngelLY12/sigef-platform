<?php

namespace App\Core\Application\Services\Events\Contracts;

use App\Core\Domain\Entities\EmailEvent;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use Illuminate\Mail\Mailable;

interface EmailEventManagerInterface
{
    public function findOrCreate(
        EmailEventType $eventType,
        EmailEventSourceType $sourceType,
        string $sourceId,
        callable $factory,
    ): EmailEvent;

    public function dispatch(
        EmailEvent $event,
        Mailable $mail,
        string $recipientEmail,
        string $jobType,
    ): void;

    public function dispatchBulk(
        array $eventIds,
        array $mailables,
        array $recipientEmails,
        string $jobType,
    ): void;

}
