<?php

namespace App\Listeners;

use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Utils\Helpers\Money;
use App\Events\AdministrationEvent;
use App\Jobs\SendBulkMailJob;
use App\Mail\CriticalAmountAlertMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SendAmoutExceededNotification
{
    /**
     * Create the event listener.
     */
    public $queue = 'default';

    public function __construct()
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
            ->select(['email', 'name' , 'last_name'])
            ->limit(4)
            ->get();
        $threshold = config('concepts.amount.notifications.threshold');
        $exceededBy = Money::from($event->amount)->sub($threshold)->finalize();
        $mailables=[];
        $recipientEmails=[];
        foreach ($mandatoryRecipients as $recipient) {
            $fullName = $recipient->name . ' ' . $recipient->last_name;
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
        SendBulkMailJob::forRecipients($mailables, $recipientEmails)
        ->onQueue('emails');

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
