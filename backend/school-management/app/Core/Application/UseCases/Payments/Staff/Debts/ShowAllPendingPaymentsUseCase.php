<?php

namespace App\Core\Application\UseCases\Payments\Staff\Debts;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;

class ShowAllPendingPaymentsUseCase
{

    public function __construct(
        private UserQueryRepInterface $uqRepo,
        private PaymentConceptQueryRepInterface $pcqRepo
    )
    {
    }
    public function execute(?string $search, int $perPage, $page): PaginatedResponse
    {
        $paginatedStudents = $this->uqRepo->findActiveStudents($search, $perPage, $page);
        $studentIds = $paginatedStudents->getCollection()->pluck('id')->toArray();
        $items = $this->pcqRepo->getPendingWithDetailsForStudents($studentIds);
        return GeneralMapper::toPaginatedResponse($items, $paginatedStudents);
    }
}
