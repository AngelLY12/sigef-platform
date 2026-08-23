<?php

namespace App\Core\Application\Services\Events\Contracts;

use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;

interface PaymentEventManagerInterface
{
    public function findOrCreate(
        string $stripeEventId,
        PaymentEventType $eventType,
        callable $factory,
    ): PaymentEvent;

    public function process(
        PaymentEvent $event,
        callable $callback,
    ): void;

    public function fail(
        PaymentEvent $event,
        \Throwable $exception,
    ): void;

}
