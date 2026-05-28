<?php

namespace Tests\Stubs\Repositories\Query;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptRelationsToDisplay;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptToDisplay;
use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Domain\Entities\User;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentConceptQueryRepStub implements PaymentConceptQueryRepInterface
{
    private ?PaymentConcept $nextFindByIdResult = null;
    private ?ConceptToDisplay $nextFindByIdToDisplayResult = null;
    private PendingSummaryResponse $nextGetPendingPaymentConceptsResult;
    private PendingSummaryResponse $nextGetOverduePaymentsSummaryResult;
    private LengthAwarePaginator $nextFindAllConceptsResult;
    private PendingSummaryResponse $nextGetAllPendingPaymentAmountResult;
    private ConceptRelationsToDisplay $nextPaymentConceptRelationsToDisplay;
    private LengthAwarePaginator $nextGetConceptsToDashboardResult;
    private array $nextGetPendingPaymentConceptsWithDetailsResult = [];
    private array $nextGetOverduePaymentsResult = [];
    private array $nextGetPendingWithDetailsForStudentsResult = [];

    public function __construct()
    {
        // Valores por defecto
        $this->nextGetPendingPaymentConceptsResult = new PendingSummaryResponse(null, null);
        $this->nextGetOverduePaymentsSummaryResult = new PendingSummaryResponse(null, null);
        $this->nextGetAllPendingPaymentAmountResult = new PendingSummaryResponse(null, null);

        // Paginators por defecto
        $this->nextFindAllConceptsResult = new LengthAwarePaginator([], 0, 15);
        $this->nextGetConceptsToDashboardResult = new LengthAwarePaginator([], 0, 15);
    }

    public function findById(int $id): ?PaymentConcept
    {
        return $this->nextFindByIdResult;
    }

    public function findByIdToDisplay(int $id): ?ConceptToDisplay
    {
        return $this->nextFindByIdToDisplayResult;
    }

    public function findRelationsByIdToDisplay(int $id): ?ConceptRelationsToDisplay
    {
        return $this->nextPaymentConceptRelationsToDisplay;
    }

    public function getPendingPaymentConcepts(User $user, bool $onlyThisYear): PendingSummaryResponse
    {
        return $this->nextGetPendingPaymentConceptsResult;
    }

    public function getOverduePaymentsSummary(User $user, bool $onlyThisYear): PendingSummaryResponse
    {
        return $this->nextGetOverduePaymentsSummaryResult;
    }

    public function findAllConcepts(string $status, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->nextFindAllConceptsResult;
    }

    public function getAllPendingPaymentAmount(bool $onlyThisYear): PendingSummaryResponse
    {
        return $this->nextGetAllPendingPaymentAmountResult;
    }

    public function getConceptsToDashboard(bool $onlyThisYear, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->nextGetConceptsToDashboardResult;
    }

    public function getPendingPaymentConceptsWithDetails(User $user): array
    {
        return $this->nextGetPendingPaymentConceptsWithDetailsResult;
    }

    public function getOverduePayments(User $user): array
    {
        return $this->nextGetOverduePaymentsResult;
    }

    public function getPendingWithDetailsForStudents(array $userIds): array
    {
        return $this->nextGetPendingWithDetailsForStudentsResult;
    }

    // Métodos de configuración
    public function setNextFindByIdResult(?PaymentConcept $concept): self
    {
        $this->nextFindByIdResult = $concept;
        return $this;
    }

    public function setNextFindByIdToDisplayResult(?ConceptToDisplay $concept): self
    {
        $this->nextFindByIdToDisplayResult = $concept;
        return $this;
    }

    public function setNextGetPendingPaymentConceptsResult(PendingSummaryResponse $response): self
    {
        $this->nextGetPendingPaymentConceptsResult = $response;
        return $this;
    }

    public function setNextGetOverduePaymentsSummaryResult(PendingSummaryResponse $response): self
    {
        $this->nextGetOverduePaymentsSummaryResult = $response;
        return $this;
    }

    public function setNextGetAllPendingPaymentAmountResult(PendingSummaryResponse $response): self
    {
        $this->nextGetAllPendingPaymentAmountResult = $response;
        return $this;
    }

    public function setNextFindAllConceptsResult(LengthAwarePaginator $paginator): self
    {
        $this->nextFindAllConceptsResult = $paginator;
        return $this;
    }

    public function setNextGetConceptsToDashboardResult(LengthAwarePaginator $paginator): self
    {
        $this->nextGetConceptsToDashboardResult = $paginator;
        return $this;
    }

    public function setNextGetPendingPaymentConceptsWithDetailsResult(array $concepts): self
    {
        $this->nextGetPendingPaymentConceptsWithDetailsResult = $concepts;
        return $this;
    }

    public function setNextGetOverduePaymentsResult(array $concepts): self
    {
        $this->nextGetOverduePaymentsResult = $concepts;
        return $this;
    }

    public function setNextGetPendingWithDetailsForStudentsResult(array $concepts): self
    {
        $this->nextGetPendingWithDetailsForStudentsResult = $concepts;
        return $this;
    }
}
