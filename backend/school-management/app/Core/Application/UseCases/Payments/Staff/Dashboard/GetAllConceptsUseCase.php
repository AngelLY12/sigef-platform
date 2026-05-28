<?php

namespace App\Core\Application\UseCases\Payments\Staff\Dashboard;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;

class GetAllConceptsUseCase
{
    public function __construct(
        private PaymentConceptQueryRepInterface $pcqRepo
    )
    {
    }
    public function execute(bool $onlyThisYear, int $perPage, int $page):PaginatedResponse
    {
        $conceptsPaginated= $this->pcqRepo->getConceptsToDashboard($onlyThisYear, $perPage, $page);
        return GeneralMapper::toPaginatedResponse($conceptsPaginated->items(),$conceptsPaginated);
    }
}
