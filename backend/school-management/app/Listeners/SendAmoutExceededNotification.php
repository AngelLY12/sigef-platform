<?php

namespace App\Listeners;

use App\Core\Application\Factories\Emails\Events\EmailEventFactory;
use App\Core\Application\Services\Events\Contracts\EmailEventManagerInterface;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Utils\Helpers\EventSourceId;
use App\Core\Domain\Utils\Helpers\Money;
use App\Events\AdministrationEvent;
use App\Mail\CriticalAmountAlertMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SendAmoutExceededNotification
{
    /**
     * Create the event listener.
     */
    public $queue = 'default';

    public function __construct(
        private EmailEventManagerInterface $emailEventManager,
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AdministrationEvent $event): void
    {
        $mandatoryRecipientsRoles = config('concepts.amount.notifications.recipient_roles');
        $mandatoryRecipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', $mandatoryRecipientsRoles))
            ->where('status', UserStatus::ACTIVO)
            ->select(['id','email', 'name' , 'last_name'])
            ->limit(4)
            ->get();
        $threshold = config('concepts.amount.notifications.threshold');
        $exceededBy = Money::from($event->amount)->sub($threshold)->finalize();
        $mailables=[];
        $recipientEmails=[];
        $eventIds = [];
        foreach ($mandatoryRecipients as $recipient) {
            $fullName = $recipient->name . ' ' . $recipient->last_name;
            $emailEvent = $this->emailEventManager->findOrCreate(
                eventType: EmailEventType::CONCEPT_CRITICAL_AMOUNT_ALERT,
                sourceType: EmailEventSourceType::CONCEPT,
                sourceId: EventSourceId::email(
                    sourceType: EmailEventSourceType::CONCEPT,
                    eventType: EmailEventType::CONCEPT_CRITICAL_AMOUNT_ALERT,
                    operationId: $event->operationId,
                    recipientId: $recipient->id,
                ),
                factory: fn () => EmailEventFactory::conceptCriticalAmountAlert(
                    userId: $recipient->id,
                    conceptId: $event->id,
                    conceptName: $event->concept_name,
                    recipientEmail: $recipient->email,
                    fullName: $fullName,
                    amount: $event->amount,
                    threshold: $threshold,
                    exceededBy: $exceededBy,
                    action: $event->action,
                    operationId: $event->operationId,
                )
            );
            $eventIds[] = $emailEvent->id;
            $mailables[]= new CriticalAmountAlertMail(
                $event->amount,
                $event->id,
                $event->concept_name,
                $fullName,
                $recipient->email,
                $threshold,
                $exceededBy,
                $event->action
            );
            $recipientEmails[] = $recipient->email;
        }
        $this->emailEventManager->dispatchBulk(
            eventIds: $eventIds,
            mailables: $mailables,
            recipientEmails: $recipientEmails,
            jobType: 'critical_amount_alert'
        );
    }
    public function failed(AdministrationEvent $event, \Throwable $exception): void
    {
        Log::critical('SendAmountExceededNotification failed', [
            'concept_name' => $event->concept_name,
            'action' => $event->action,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
