<?php

namespace App\Listeners;

use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Events\PaymentConceptUpdatedFields;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendConceptUpdatedFieldsNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public $queue = 'default';
    public $delay = 5;
    public function __construct(
        private PaymentConceptQueryRepInterface $pcqRepo,
        private UserQueryRepInterface $uqRepo,
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentConceptUpdatedFields $event): void
    {
        $concept=$this->pcqRepo->findById($event->conceptId);
        $userIds=$this->uqRepo->getRecipientsIds($concept, $concept->applies_to->value);

        if (empty($event->userIds)) {
            Log::info('No user IDs to notify', ['concept_id' => $event->conceptId]);
            return;
        }

        $notification = new \App\Notifications\PaymentConceptUpdatedFields($concept->toArray(), $event->changes);


        foreach (array_chunk($event->userIds, 500) as $chunk) {
            \App\Models\User::whereIn('id', $chunk)
                ->each(function ($user) use ($notification) {
                    $user->notify($notification);
                });

            usleep(100000);
        }

        Log::info('Broadcast notifications job completed', [
            'concept_id' => $event->conceptId,
            'changes_count' => count($event->changes),
            'queue' => $this->queue
        ]);

    }
    public function failed(PaymentConceptUpdatedFields $event, \Throwable $exception): void
    {
        Log::error('Failed to send broadcast notifications', [
            'concept_id' => $event->conceptId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
