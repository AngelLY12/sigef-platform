<?php

namespace App\Listeners;

use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Events\ParentStudentRelationDelete;
use App\Notifications\ParentStudentDeleteNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendParentStudentDeleteNotification implements ShouldQueue
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
    public function handle(ParentStudentRelationDelete $event): void
    {
        $parent= $this->userQueryRep->findModelEntity($event->parentId);
        $student= $this->userQueryRep->findModelEntity($event->studentId);
        $parent->notify(new ParentStudentDeleteNotification(
            $student->name . ' ' . $student->last_name
        ));
    }

    public function failed(ParentStudentRelationDelete $event, \Throwable $exception): void
    {
        Log::critical('SendParentStudentDeleteNotification failed', [
            'parent_id' => $event->parentId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

}
