<?php

namespace App\Core\Application\Services\Events;

use App\Core\Application\Services\Events\Contracts\EmailEventManagerInterface;
use App\Core\Domain\Entities\EmailEvent;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\Repositories\Command\Events\EmailEventRepInterface;
use App\Core\Domain\Repositories\Query\Events\EmailEventQueryRepInterface;
use App\Jobs\SendBulkMailJob;
use App\Jobs\SendMailJob;
use Illuminate\Mail\Mailable;

final class EmailEventManager implements EmailEventManagerInterface
{
    public function __construct(
        private EmailEventQueryRepInterface $emailEventQueryRep,
        private EmailEventRepInterface $emailEventRep,
    ) {}
    public function findOrCreate(EmailEventType $eventType, EmailEventSourceType $sourceType, string $sourceId, callable $factory): EmailEvent
    {
        $existing = $this->emailEventQueryRep->findBySource(
            sourceType: $sourceType,
            sourceId: $sourceId,
            eventType: $eventType,
        );

        if ($existing) {
            return $existing;
        }

        $event = $factory();

        return $this->emailEventRep->create($event);
    }

    public function dispatch(EmailEvent $event, Mailable $mail, string $recipientEmail, string $jobType): void
    {
        if ($event->id === null) {
            throw new \InvalidArgumentException(
                'Cannot dispatch an email without an EmailEvent ID.'
            );
        }

        if ($event->alreadySent() || $event->alreadyDelivered()) {
            return;
        }

        SendMailJob::forUser(
            mailable: $mail,
            recipientEmail: $recipientEmail,
            jobType: $jobType,
            emailEventId: $event->id,
        )->onQueue('emails');
    }

    public function dispatchBulk(array $eventIds, array $mailables, array $recipientEmails, string $jobType): void
    {
        if (
            count($eventIds) !== count($mailables) ||
            count($eventIds) !== count($recipientEmails)
        ) {
            throw new \InvalidArgumentException(
                'Event IDs, mailables y emails deben tener la misma cantidad.'
            );
        }

        if (empty($eventIds)) {
            return;
        }

        SendBulkMailJob::forRecipients(
            mailables: $mailables,
            recipientEmails: $recipientEmails,
            emailEventIds: $eventIds,
            jobType: $jobType,
        )->onQueue('emails');
    }

}
