<?php

namespace App\Core\Domain\Repositories\Query\Payments;

use App\Core\Application\DTO\Response\PaymentConcept\ConceptRelationsToDisplay;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptToDisplay;
use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Domain\Entities\User;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Core\Domain\Entities\PaymentConcept;

interface PaymentConceptQueryRepInterface{
    //Dashboard Student
    public function findById(int $id): ?PaymentConcept;
    public function findByIdToDisplay(int $id): ?ConceptToDisplay;
    public function findRelationsByIdToDisplay(int $id): ?ConceptRelationsToDisplay;
    public function getPendingPaymentConcepts(User $user, bool $onlyThisYear): PendingSummaryResponse;
    public function getOverduePaymentsSummary(User $user, bool $onlyThisYear): PendingSummaryResponse;
    //Dashboard Staff
    public function findAllConcepts(string $status, int $perPage, int $page): LengthAwarePaginator;
    public function getAllPendingPaymentAmount(bool $onlyThisYear): PendingSummaryResponse;
    public function getConceptsToDashboard(bool $onlyThisYear,int $perPage, int $page): LengthAwarePaginator;
    public function getPendingPaymentConceptsWithDetails(User $user):array;
    public function getOverduePayments(User $user):array;
    public function getPendingWithDetailsForStudents(array $userIds): array;
}
