<?php

namespace App\Core\Application\Services\Payments\Staff;

use App\Core\Application\DTO\Request\PaymentConcept\CreatePaymentConceptDTO;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptDTO;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptRelationsDTO;
use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptChangeStatusResponse;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptRelationsToDisplay;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptToDisplay;
use App\Core\Application\DTO\Response\PaymentConcept\CreatePaymentConceptResponse;
use App\Core\Application\DTO\Response\PaymentConcept\UpdatePaymentConceptRelationsResponse;
use App\Core\Application\DTO\Response\PaymentConcept\UpdatePaymentConceptResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Payments\Staff\Concepts\FindConceptByIdUseCase;
use App\Core\Application\UseCases\Payments\Staff\Concepts\FindConceptRelationsToDisplay;
use App\Core\Application\UseCases\Payments\Staff\Concepts\FindControlNumbersBySearchUseCase;
use App\Core\Application\UseCases\Payments\Staff\Concepts\UpdatePaymentConceptRelationsUseCase;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Application\UseCases\Payments\Staff\Concepts\ActivatePaymentConceptUseCase;
use App\Core\Application\UseCases\Payments\Staff\Concepts\CreatePaymentConceptUseCase;
use App\Core\Application\UseCases\Payments\Staff\Concepts\DisablePaymentConceptUseCase;
use App\Core\Application\UseCases\Payments\Staff\Concepts\EliminateLogicalPaymentConceptUseCase;
use App\Core\Application\UseCases\Payments\Staff\Concepts\EliminatePaymentConceptUseCase;
use App\Core\Application\UseCases\Payments\Staff\Concepts\FinalizePaymentConceptUseCase;
use App\Core\Application\UseCases\Payments\Staff\Concepts\ShowConceptsUseCase;
use App\Core\Application\UseCases\Payments\Staff\Concepts\UpdatePaymentConceptFieldsUseCase;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StaffCacheSufix;
use App\Core\Infraestructure\Cache\CacheService;

class ConceptsServiceFacades{
    use HasCache;

    private const TAGS_CONCEPTS_LIST = [CachePrefix::STAFF->value, StaffCacheSufix::CONCEPTS->value,"list"];
    private const TAG_CONCEPT_BY_ID = [CachePrefix::STAFF->value, StaffCacheSufix::CONCEPTS->value, "concept"];
    private const TAG_CONCEPT_RELATIONS = [CachePrefix::STAFF->value, StaffCacheSufix::CONCEPTS->value, "relation"];
    private const TAG_CONCEPT_N_CONTROL_SEARCH = [CachePrefix::STAFF->value, StaffCacheSufix::CONCEPTS->value, "search"];

    public function __construct(
        private ShowConceptsUseCase                   $show,
        private CreatePaymentConceptUseCase           $create,
        private UpdatePaymentConceptFieldsUseCase     $update,
        private UpdatePaymentConceptRelationsUseCase $updateRelations,
        private FinalizePaymentConceptUseCase         $finalize,
        private DisablePaymentConceptUseCase          $disable,
        private EliminatePaymentConceptUseCase        $eliminate,
        private EliminateLogicalPaymentConceptUseCase $eliminateLogical,
        private ActivatePaymentConceptUseCase         $activate,
        private FindConceptByIdUseCase $concept,
        private FindConceptRelationsToDisplay $relations,
        private FindControlNumbersBySearchUseCase $findControlNumbers,
        private CacheService                          $service
    )
    {
        $this->setCacheService($service);

    }

    public function showConcepts(string $status, int $perPage, int $page, bool $forceRefresh): PaginatedResponse{
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::CONCEPTS->value . ":list",
            ['status' => $status, 'page' => $page, 'perPage' => $perPage]
        );
        return $this->mediumCache($key,fn() => $this->show->execute($status, $perPage, $page),self::TAGS_CONCEPTS_LIST ,$forceRefresh);
    }

    public function findConcept(int $id, bool $forceRefresh): ConceptToDisplay
    {
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::CONCEPTS->value . ":concept",
            ['id' => $id]
        );
        return $this->shortCache($key, fn() => $this->concept->execute($id), self::TAG_CONCEPT_BY_ID ,$forceRefresh);
    }

    public function findRelations(int $id, bool $forceRefresh): ConceptRelationsToDisplay
    {
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::CONCEPTS->value . ":relation",
            ['id' => $id]
        );
        return $this->shortCache($key, fn() => $this->relations->execute($id), self::TAG_CONCEPT_RELATIONS, $forceRefresh);
    }

    public function findNumberControlsBySearch(string $search, int $limit, bool $forceRefresh): array
    {
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::CONCEPTS->value . ":search",
            ['search' => $search, 'limit' => $limit]
        );
        return $this->mediumCache($key, fn() => $this->findControlNumbers->execute($search, $limit), self::TAG_CONCEPT_N_CONTROL_SEARCH, $forceRefresh);
    }

    public function createPaymentConcept(CreatePaymentConceptDTO $dto): CreatePaymentConceptResponse {
        return $this->idempotent(
            'payment_concept_create',
                $dto->toArray(),
            function () use ($dto) {
                $concept = $this->create->execute($dto);
                $this->service->flushTags(self::TAGS_CONCEPTS_LIST);
                return $concept;
            }
        );
    }

    public function updatePaymentConcept(UpdatePaymentConceptDTO $dto): UpdatePaymentConceptResponse {
        return $this->idempotent(
            'payment_concept_update',
            $dto->toArray(),
            function () use ($dto) {
                $concept = $this->update->execute($dto);
                $this->service->flushTags(self::TAGS_CONCEPTS_LIST);
                return $concept;
            }
        );
    }

    public function updatePaymentConceptRelations(UpdatePaymentConceptRelationsDTO $dto): UpdatePaymentConceptRelationsResponse
    {
        return $this->idempotent(
            'payment_concept_relations_update',
            $dto->toArrayEntire(),
            function () use ($dto) {
                $concept= $this->updateRelations->execute($dto);
                $this->service->flushTags(self::TAGS_CONCEPTS_LIST);
                return $concept;
            }
        );
    }

    public function finalizePaymentConcept(PaymentConcept $concept): ConceptChangeStatusResponse {
        return $this->idempotent(
            'payment_concept_finalize',
            ['concept_id' => $concept->id],
            function () use ($concept) {
                $result = $this->finalize->execute($concept);
                $this->service->flushTags(self::TAGS_CONCEPTS_LIST);
                return $result;
            }
        );
    }

    public function disablePaymentConcept(PaymentConcept $concept): ConceptChangeStatusResponse {
        return $this->idempotent(
            'payment_concept_disable',
            ['concept_id' => $concept->id],
            function () use ($concept) {
                $result = $this->disable->execute($concept);
                $this->service->flushTags(self::TAGS_CONCEPTS_LIST);
                return $result;
            }
        );
    }

    public function eliminatePaymentConcept(int $conceptId): void {
        $this->idempotent(
            'payment_concept_delete',
            ['concept_id' => $conceptId],
            function () use ($conceptId) {
                $this->eliminate->execute($conceptId);
                $this->service->flushTags(self::TAGS_CONCEPTS_LIST);
                return true;
            }
        );
    }

    public function activatePaymentConcept(PaymentConcept $concept):ConceptChangeStatusResponse
    {
        return $this->idempotent(
            'payment_concept_activate',
            ['concept_id' => $concept->id],
            function () use ($concept) {
                $result = $this->activate->execute($concept);
                $this->service->flushTags(self::TAGS_CONCEPTS_LIST);
                return $result;
            }
        );
    }

    public function eliminateLogicalPaymentConcept(PaymentConcept $concept): ConceptChangeStatusResponse{
        return $this->idempotent(
            'payment_concept_soft_delete',
            ['concept_id' => $concept->id],
            function () use ($concept) {
                $result = $this->eliminateLogical->execute($concept);
                $this->service->flushTags(self::TAGS_CONCEPTS_LIST);
                return $result;
            }
        );
    }
}
