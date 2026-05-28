<?php

namespace App\Listeners;

use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Events\StudentsPromotionFailed;
use App\Notifications\PromotionFailedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendStudentsPromotionFailedNotification implements ShouldQueue
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
    public function handle(StudentsPromotionFailed $event): void
    {
        $admin=$this->uqRepo->findModelEntity($event->adminId);
        if (! $admin) {
            return;
        }
        $admin->notify(new PromotionFailedNotification(
            $event->error
        ));
    }
    public function failed(StudentsPromotionFailed $event, \Throwable $exception): void
    {
        Log::critical('SendStudentsPromotionFailedNotification failed', [
            'admin_id' => $event->adminId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
