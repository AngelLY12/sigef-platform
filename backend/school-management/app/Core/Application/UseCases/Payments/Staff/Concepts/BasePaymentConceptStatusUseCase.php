<?php

namespace App\Core\Application\UseCases\Payments\Staff\Concepts;

use App\Core\Application\DTO\Response\PaymentConcept\ConceptChangeStatusResponse;
use App\Core\Application\Mappers\PaymentConceptMapper;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Repositories\Command\Payments\PaymentConceptRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Utils\Validators\PaymentConceptValidator;
use App\Events\PaymentConceptStatusChanged;
use App\Jobs\ClearCacheForUsersJob;

abstract class BasePaymentConceptStatusUseCase
{
    protected const CHUNK_SIZE = 500;
    protected const CACHE_DELAY = 2;
    public function __construct(
        protected PaymentConceptRepInterface $pcRepo,
        protected UserQueryRepInterface $uqRepo
    ) {}

    abstract protected function getTargetStatus(): PaymentConceptStatus;
    abstract protected function getRepositoryMethod(): string;
    abstract protected function getSuccessMessage(): string;

    public function execute(PaymentConcept $concept): ConceptChangeStatusResponse
    {
        PaymentConceptValidator::ensureValidStatusTransition(
            $concept,
            $this->getTargetStatus()
        );

        $updatedConcept = $this->updateConceptStatus($concept);
        $userIds = $this->getAffectedUserIds($updatedConcept);
        $this->dispatchStatusChangedNotification($concept, $updatedConcept);
        $this->dispatchCacheClearJobs($userIds);
        return $this->formattResponse($concept, $updatedConcept);
    }

    private function formattResponse(PaymentConcept $concept, PaymentConcept $updatedConcept): ConceptChangeStatusResponse
    {
        $data=[
            'message' => $this->getSuccessMessage(),
            'changes' => [
                [
                    'field' => 'status',
                    'old' =>$concept->status->value,
                    'new' => $updatedConcept->status->value,
                    'type'=> 'status_change',
                    'transition_type' => $this->getRepositoryMethod()
                ]
            ]
        ];
        return PaymentConceptMapper::toConceptChangeStatusResponse($updatedConcept,$data);
    }



    protected function getAffectedUserIds(PaymentConcept $concept): array
    {
        return $this->uqRepo->getRecipientsIds($concept, $concept->applies_to->value);
    }

    protected function dispatchCacheClearJobs(array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }

        foreach (array_chunk($userIds, self::CHUNK_SIZE) as $chunk) {
            ClearCacheForUsersJob::forConceptStatus($chunk, $this->getTargetStatus())
                ->onQueue('cache')
                ->delay(now()->addSeconds(self::CACHE_DELAY));
        }
    }

    private function dispatchStatusChangedNotification(PaymentConcept $originalConcept, PaymentConcept $newConcept): void
    {
        if ($originalConcept->status !== $newConcept->status) {
            event(new PaymentConceptStatusChanged($newConcept->id, $originalConcept->status->value, $newConcept->status->value));
        }
    }
    protected function updateConceptStatus(PaymentConcept $concept): PaymentConcept
    {
        $method = $this->getRepositoryMethod();
        return $this->pcRepo->{$method}($concept);
    }

}
