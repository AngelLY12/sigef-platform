<?php

namespace App\Core\Application\Mappers;

use App\Core\Application\DTO\Response\PaymentMethod\DisplayPaymentMethodResponse;
use App\Core\Application\DTO\Response\PaymentMethod\SetupCardResponse;
use App\Core\Domain\Entities\PaymentMethod as DomainPaymentMethod;
use Stripe\Checkout\Session;

class PaymentMethodMapper{

    public static function toDisplayPaymentMethodResponse(DomainPaymentMethod $method):DisplayPaymentMethodResponse
    {
        $status = $method->isExpired() ? 'Caducada' : 'Vigente';

        return new DisplayPaymentMethodResponse(
            id: $method->id ?? null,
            brand: $method->brand ?? 'Desconocido',
            masked_card: $method->getMaskedCard() ?? '**** **** **** ****',
            expiration_date: $method->expirationDate() ?? null,
            status: $status
        );
    }

    public static function toSetupCardResponse(Session $session):SetupCardResponse
    {
        return new SetupCardResponse(
            id:$session->id ?? null,
            url:$session->url ?? null
        );
    }
}
