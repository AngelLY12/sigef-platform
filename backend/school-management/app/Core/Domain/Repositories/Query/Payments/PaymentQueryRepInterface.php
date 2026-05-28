<?php

namespace App\Core\Domain\Repositories\Query\Payments;

use App\Core\Application\DTO\Response\Payment\PaymentToDisplay;
use App\Core\Domain\Entities\Payment;
use Generator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PaymentQueryRepInterface{
    public function findBySessionId(string $sessionId): ?Payment;
    public function findById(int $id): ?Payment;
    public function findByIdToDisplay(int $id): ?PaymentToDisplay;
    public function findByIds(array $ids): Collection;
    public function findByIntentId(string $intentId): ?Payment;
    public function sumPaymentsByUserYear(int $userId, bool $onlyThisYear): array;
    public function getPaymentHistory(int $userId, int $perPage, int $page, bool $onlyThisYear): LengthAwarePaginator;
    //Dashboard Staff
    public function getAllPaymentsMade(bool $onlyThisYear):array;
    //Others
    public function getPaymentHistoryWithDetails(int $userId, int $perPage, int $page): LengthAwarePaginator;
    public function findByIntentOrSession(int $userId, string $paymentIntentId): ?Payment;
//    public function getAllWithSearch(?string $search = null, int $perPage = 15): LengthAwarePaginator;
    public function getReconciliablePaymentsCursor(): Generator;
    public function getAllWithSearchEager(?string $search, int $perPage,int $page): LengthAwarePaginator;
    public function getLastPaymentForConcept(int $userId, int $conceptId, array $allowedStatuses = []): ?Payment;
    public function getPaymentsByConceptName(int $perPage, int $page, ?string $search=null): LengthAwarePaginator;
}
