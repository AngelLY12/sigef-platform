<?php

namespace App\Core\Application\UseCases\Payments\Staff\Concepts;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Infraestructure\Mappers\PaymentConceptMapper;

class ShowConceptsUseCase
{
        public function __construct(
        private PaymentConceptQueryRepInterface $pcqRepo,

    )
    {}
    public function execute(string $status, int $perPage, int $page): PaginatedResponse
    {

        $paginated = $this->pcqRepo->findAllConcepts($status, $perPage, $page);

        $items = $paginated->getCollection()
            ->values()
            ->toArray();

        return GeneralMapper::toPaginatedResponse($items, $paginated);
    }
}
