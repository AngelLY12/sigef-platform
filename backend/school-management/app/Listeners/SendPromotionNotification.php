<?php

namespace App\Listeners;

use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Events\StudentsPromotionCompleted;
use App\Notifications\PromotionCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPromotionNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public $queue = 'default';

    public function __construct(
        private UserQueryRepInterface $uqRepo,
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(StudentsPromotionCompleted $event): void
    {
        $admin=$this->uqRepo->findModelEntity($event->adminId);
        if (! $admin) {
            return;
        }
        $admin->notify(new PromotionCompletedNotification(
            $event->promotedCount,
            $event->desactivatedCount
        ));
    }

    public function failed(StudentsPromotionCompleted $event, \Throwable $exception): void
    {
        Log::critical('SendPromotionNotification failed', [
            'admin_id' => $event->adminId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
