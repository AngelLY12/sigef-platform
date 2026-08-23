<?php

namespace App\Core\Application\UseCases\Admin\Events\PaymentEvents;

use App\Core\Application\DTO\Response\Events\PaymentEvent\PaymentEventByIdResponse;
use App\Core\Domain\Repositories\Query\Events\PaymentEventQueryRepInterface;
use App\Exceptions\NotFound\NotFoundException;

class GetPaymentEventByIdUseCase
{
    public function __construct(
        private PaymentEventQueryRepInterface $paymentEventQueryRep,
    ){}

    public function execute(int $paymentEventId): PaymentEventByIdResponse
    {
        $paymentEvent = $this->paymentEventQueryRep->findById($paymentEventId);
        if(!$paymentEvent){
            throw new NotFoundException('No se encontro el evento de pago solicitado');
        }
        return PaymentEventByIdResponse::create($paymentEvent);
    }

}
