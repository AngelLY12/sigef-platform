<?php

namespace App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata;

interface PaymentEventMetadata
{
    public function toArray(): array;
}
