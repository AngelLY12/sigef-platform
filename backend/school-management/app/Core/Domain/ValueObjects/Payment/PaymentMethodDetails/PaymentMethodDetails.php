<?php

namespace App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails;

interface PaymentMethodDetails
{
    public function toArray(): array;
    public function type(): string;

}
