<?php

namespace App\Core\Application\UseCases\Jobs;

use App\Core\Application\Services\Events\Contracts\EmailEventManagerInterface;
use App\Core\Application\Traits\HasPaymentConcept;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Jobs\SendConceptUpdatedRelationsNotificationJob;
use Illuminate\Support\Facades\Log;

class ProcessPaymentConceptRecipientsUseCase
{
    use HasPaymentConcept;

    public function __construct(
        private UserQueryRepInterface               $uqRepo,
        private readonly EmailEventManagerInterface $emailEventManager,
    )
    {
        $this->setRepository($uqRepo);
        $this->setEmailEventManager($this->emailEventManager);
    }

    public function execute(PaymentConcept $paymentConcept, string $appliesTo, string $operationId): void
    {
        $recipients = $this->uqRepo->getRecipients($paymentConcept, $appliesTo);
        if(empty($recipients)){
            Log::warning('Payment concept created but no recipients found for notifications', [
                'concept_id' => $paymentConcept->id,
                'applies_to' => $appliesTo
            ]);
            return;
        }
        $this->notifyRecipients($paymentConcept,$recipients, $operationId);
        $userIds=[];
        foreach ($recipients as $recipient)
        {
            $userIds[]=$recipient->id;
        }
        $this->sendBroadcasteForCreatedConcept($paymentConcept, $userIds);
        Log::info('Payment concept creation notifications sent', [
            'concept_id' => $paymentConcept->id,
            'applies_to' => $appliesTo,
            'recipient_count' => count($recipients),
            'broadcast_count' => count($userIds)
        ]);
    }

    private function sendBroadcasteForCreatedConcept(PaymentConcept $newConcept, array $userIds): void
    {
        $changes = [
            [
                'type' => 'created_concept',
                'field' => 'all_required_fields_added'
            ]
        ];
        SendConceptUpdatedRelationsNotificationJob::forStudents(
            $userIds,
            $newConcept->id,
            $changes
        )
            ->onQueue('default')
            ->delay(now()->addSeconds(5));
    }


}
