<?php
namespace App\Core\Application\UseCases\Payments\Staff\Concepts;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Repositories\Command\Payments\PaymentConceptRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Exceptions\NotFound\ConceptNotFoundException;
use App\Jobs\ClearCacheForUsersJob;

class EliminatePaymentConceptUseCase
{
    private const CHUNK_SIZE = 500;
    private const CACHE_DELAY = 5;
    public function __construct(
        private PaymentConceptRepInterface $pcRepo,
        private PaymentConceptQueryRepInterface $pcqRepo,
        private UserQueryRepInterface $uqRepo
    )
    {}

    public function execute(int $conceptId):void
    {

        $concept=$this->pcqRepo->findById($conceptId);
        if(!$concept){
            throw new ConceptNotFoundException();
        }
        $userIds = $this->uqRepo->getRecipientsIds($concept, $concept->applies_to->value);
        $this->dispatchCacheClearJobs($userIds);
        $this->pcRepo->delete($conceptId);
    }
    private function dispatchCacheClearJobs(array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }

        foreach (array_chunk($userIds, self::CHUNK_SIZE) as $chunk) {
            ClearCacheForUsersJob::forConceptStatus($chunk, PaymentConceptStatus::ELIMINADO)
                ->onQueue('cache')
                ->delay(now()->addSeconds(self::CACHE_DELAY));
        }
    }
}
