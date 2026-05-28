<?php

namespace App\Core\Domain\Repositories\Command\Payments;

use App\Core\Domain\Entities\PaymentEvent;

interface PaymentEventRepInterface
{
    public function create(PaymentEvent $event): PaymentEvent;
    public function update(int $paymentEventId, array $fields): PaymentEvent;
    public function deleteOlderEvents(): int;

}
