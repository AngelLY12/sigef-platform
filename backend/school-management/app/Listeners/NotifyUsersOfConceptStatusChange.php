<?php

namespace App\Listeners;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Events\PaymentConceptStatusChanged;
use App\Notifications\PaymentConceptStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifyUsersOfConceptStatusChange implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public $queue = 'default';
    public function __construct(
        private PaymentConceptQueryRepInterface $pcqRep,
        private UserQueryRepInterface $uqRep
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentConceptStatusChanged $event): void
    {
        if (!$this->shouldNotify($event->oldStatus, $event->newStatus)) {
            return;
        }
        $concept=$this->pcqRep
            ->findById($event->conceptId);

        if (!$concept) {
            Log::warning('Payment concept not found for notification listener', [
                'concept_id' => $event->conceptId
            ]);
            return;
        }

        $userIds = $this->uqRep
            ->getRecipientsIds($concept, $concept->applies_to->value);

        if (empty($userIds)) {
            return;
        }

        foreach (array_chunk($userIds, 500) as $chunk) {
            $users = \App\Models\User::whereIn('id', $chunk)->get(['id']);

            if ($users->isEmpty()) {
                continue;
            }
            Notification::send($users, new PaymentConceptStatusUpdated(
                concept: $concept->toArray(),
                oldStatus: $event->oldStatus,
                newStatus: $event->newStatus
            ));

        }
    }

    private function shouldNotify(string $oldStatus, string $newStatus): bool
    {
        if ($oldStatus === $newStatus) {
            return false;
        }

        $relevantTransitions = [
            PaymentConceptStatus::ACTIVO->value => [
                PaymentConceptStatus::FINALIZADO->value,
                PaymentConceptStatus::DESACTIVADO->value,
                PaymentConceptStatus::ELIMINADO->value,
            ],
            '*' => [PaymentConceptStatus::ACTIVO->value],
        ];

        $fromActive = $oldStatus === PaymentConceptStatus::ACTIVO->value
            && in_array($newStatus, $relevantTransitions[PaymentConceptStatus::ACTIVO->value]);

        $toActive = $newStatus === PaymentConceptStatus::ACTIVO->value;

        return $fromActive || $toActive;
    }
    public function failed(PaymentConceptStatusChanged $event, \Throwable $exception): void
    {
        Log::critical('NotifyUsersOfConceptStatusChange failed', [
            'concept_id' => $event->conceptId,
            'new_status' => $event->newStatus,
            'old_status' => $event->oldStatus,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
