<?php

namespace App\Listeners;

use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Events\ParentInvitationAccepted;
use App\Events\ParentInvitationFailed;
use App\Notifications\ParentInvitationAcceptedNotification;
use App\Notifications\ParentInvitationFailedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendParentInvitationFailedNotification
{
    /**
     * Create the event listener.
     */
    public $queue = 'default';

    public function __construct(
        private UserQueryRepInterface $userQueryRep,
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ParentInvitationFailed $event): void
    {
        $recipient= $this->userQueryRep->findModelEntity($event->studentId);
        $recipient->notify(new ParentInvitationFailedNotification(
            $event->parentFullName,
            $event->studentFullName));
    }

    public function failed(ParentInvitationFailed $event, \Throwable $exception): void
    {
        Log::critical('SendParentInvitationAcceptedNotification failed', [
            'student_id' => $event->studentId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
