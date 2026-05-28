<?php

namespace App\Core\Application\UseCases\Payments\Staff\Payments;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Application\Mappers\PaymentMapper;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;

class ShowAllPaymentUseCase{
    public function __construct(
        private PaymentQueryRepInterface $pqRepo
    )
    {
    }
    public function execute(?string $search, int $perPage, $page): PaginatedResponse
    {
        $paginated = $this->pqRepo->getAllWithSearchEager($search, $perPage, $page);
        return GeneralMapper::toPaginatedResponse($paginated->items(),$paginated);
    }
}
