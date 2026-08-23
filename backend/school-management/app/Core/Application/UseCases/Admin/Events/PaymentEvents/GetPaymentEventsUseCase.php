<?php

namespace App\Core\Application\UseCases\Admin\Events\PaymentEvents;

use App\Core\Application\DTO\Request\Events\PaymentEvent\PaymentEventFilters;
use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Repositories\Query\Events\PaymentEventQueryRepInterface;

class GetPaymentEventsUseCase
{
    public function __construct(
        private PaymentEventQueryRepInterface $paymentEventQueryRep,
    ){}

    public function execute(PaymentEventFilters $filters): PaginatedResponse
    {
        $paymentEvents = $this->paymentEventQueryRep->getAllPaymentEvents($filters);
        return GeneralMapper::toPaginatedResponse($paymentEvents->items(), $paymentEvents);
    }

}
