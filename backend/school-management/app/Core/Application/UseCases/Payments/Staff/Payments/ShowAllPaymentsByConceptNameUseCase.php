<?php

namespace App\Core\Application\UseCases\Payments\Staff\Payments;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;

class ShowAllPaymentsByConceptNameUseCase
{
    public function __construct(
        private PaymentQueryRepInterface $paymentQueryRep,
    )
    {}

    public function execute(?string $search, int $perPage, int $page): PaginatedResponse
    {
        $payments=$this->paymentQueryRep->getPaymentsByConceptName($perPage, $page, $search);
        return GeneralMapper::toPaginatedResponse($payments->items(), $payments);
    }

}
