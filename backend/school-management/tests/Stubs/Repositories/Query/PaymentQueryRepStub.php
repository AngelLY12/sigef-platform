<?php

namespace Tests\Stubs\Repositories\Query;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Generator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentQueryRepStub implements PaymentQueryRepInterface
{
    private ?Payment $nextFindBySessionIdResult = null;
    private ?Payment $nextFindByIdResult = null;
    private ?Payment $nextFindByIntentIdResult = null;
    private Collection $nextFindByIdsResult;
    private array $nextSumPaymentsByUserYearResult = [];
    private LengthAwarePaginator $nextGetPaymentHistoryResult;
    private array $nextGetAllPaymentsMadeResult = [];
    private LengthAwarePaginator $nextGetPaymentHistoryWithDetailsResult;
    private ?Payment $nextFindByIntentOrSessionResult = null;
    private LengthAwarePaginator $nextGetAllWithSearchEagerResult;
    private Generator $nextGetPaidWithinLastMonthCursorResult;
    private ?Payment $nextGetLastPaymentForConceptResult = null;

    public function __construct()
    {
        // Valores por defecto
        $this->nextGetPaymentHistoryResult = new LengthAwarePaginator([], 0, 15);
        $this->nextGetPaymentHistoryWithDetailsResult = new LengthAwarePaginator([], 0, 15);
        $this->nextGetAllWithSearchEagerResult = new LengthAwarePaginator([], 0, 15);
        $this->nextGetPaidWithinLastMonthCursorResult = $this->createEmptyGenerator();
    }

    public function findBySessionId(string $sessionId): ?Payment
    {
        return $this->nextFindBySessionIdResult;
    }

    public function findById(int $id): ?Payment
    {
        return $this->nextFindByIdResult;
    }

    public function findByIds(array $ids): Collection
    {
        return $this->nextFindByIdsResult;
    }

    public function findByIntentId(string $intentId): ?Payment
    {
        return $this->nextFindByIntentIdResult;
    }

    public function sumPaymentsByUserYear(int $userId, bool $onlyThisYear): array
    {
        return $this->nextSumPaymentsByUserYearResult;
    }

    public function getPaymentHistory(int $userId, int $perPage, int $page, bool $onlyThisYear): LengthAwarePaginator
    {
        return $this->nextGetPaymentHistoryResult;
    }

    public function getAllPaymentsMade(bool $onlyThisYear): array
    {
        return $this->nextGetAllPaymentsMadeResult;
    }

    public function getPaymentHistoryWithDetails(int $userId, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->nextGetPaymentHistoryWithDetailsResult;
    }

    public function findByIntentOrSession(int $userId, string $paymentIntentId): ?Payment
    {
        return $this->nextFindByIntentOrSessionResult;
    }

    public function getReconciliablePaymentsCursor(): Generator
    {
        foreach ($this->nextGetPaidWithinLastMonthCursorResult as $payment) {
            yield $payment;
        }
    }

    public function getAllWithSearchEager(?string $search, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->nextGetAllWithSearchEagerResult;
    }

    public function getLastPaymentForConcept(int $userId, int $conceptId, array $allowedStatuses = []): ?Payment
    {
        return $this->nextGetLastPaymentForConceptResult;
    }

    // Métodos de configuración
    public function setNextFindBySessionIdResult(?Payment $payment): self
    {
        $this->nextFindBySessionIdResult = $payment;
        return $this;
    }

    public function setNextFindByIdResult(?Payment $payment): self
    {
        $this->nextFindByIdResult = $payment;
        return $this;
    }

    public function setNextFindByIntentIdResult(?Payment $payment): self
    {
        $this->nextFindByIntentIdResult = $payment;
        return $this;
    }

    public function setNextSumPaymentsByUserYearResult(array $result): self
    {
        $this->nextSumPaymentsByUserYearResult = $result;
        return $this;
    }

    public function setNextGetPaymentHistoryResult(LengthAwarePaginator $paginator): self
    {
        $this->nextGetPaymentHistoryResult = $paginator;
        return $this;
    }

    public function setNextGetAllPaymentsMadeResult(array $result): self
    {
        $this->nextGetAllPaymentsMadeResult = $result;
        return $this;
    }

    public function setNextGetPaymentHistoryWithDetailsResult(LengthAwarePaginator $paginator): self
    {
        $this->nextGetPaymentHistoryWithDetailsResult = $paginator;
        return $this;
    }

    public function setNextFindByIntentOrSessionResult(?Payment $payment): self
    {
        $this->nextFindByIntentOrSessionResult = $payment;
        return $this;
    }

    public function setNextGetAllWithSearchEagerResult(LengthAwarePaginator $paginator): self
    {
        $this->nextGetAllWithSearchEagerResult = $paginator;
        return $this;
    }

    public function getPaymentsByConceptName(?string $search = null, int $perPage, int $page): LengthAwarePaginator
    {
        return LengthAwarePaginator::empty();
    }

    public function setNextGetPaidWithinLastMonthCursorResult(Generator $generator): self
    {
        $this->nextGetPaidWithinLastMonthCursorResult = $generator;
        return $this;
    }

    public function setNextGetLastPaymentForConceptResult(?Payment $payment): self
    {
        $this->nextGetLastPaymentForConceptResult = $payment;
        return $this;
    }

    private function setNextFindByIdsResult(Collection $payments): self
    {
        $this->nextFindByIdsResult = $payments;
        return $this;
    }


    private function createEmptyGenerator(): Generator
    {
        return (function () {
            yield from [];
        })();
    }
}
