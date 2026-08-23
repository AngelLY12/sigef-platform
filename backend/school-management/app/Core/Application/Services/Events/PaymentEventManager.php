<?php

namespace App\Core\Application\Services\Events;

use App\Core\Application\Services\Events\Contracts\PaymentEventManagerInterface;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Repositories\Command\Events\PaymentEventRepInterface;
use App\Core\Domain\Repositories\Query\Events\PaymentEventQueryRepInterface;

final class PaymentEventManager implements PaymentEventManagerInterface
{
    public function __construct(
        private PaymentEventQueryRepInterface $queryRep,
        private PaymentEventRepInterface $repository,
    ) {}
    public function findOrCreate(string $stripeEventId, PaymentEventType $eventType, callable $factory): PaymentEvent
    {
        $existing = $this->queryRep->findByStripeEvent(
            $stripeEventId,
            $eventType
        );

        if ($existing) {
            return $existing;
        }

        $event = $factory();

        return $this->repository->create($event);
    }

    public function process(PaymentEvent $event, callable $callback): void
    {
        try {
            $callback();

            $event->markAsProcessed();

            $this->repository->save($event);
        } catch (\Throwable $exception) {
            $this->fail($event, $exception);

            throw $exception;
        }
    }

    public function fail(PaymentEvent $event, \Throwable $exception): void
    {
        $event->registerRetry();
        $event->markAsFailed($exception->getMessage());

        $this->repository->save($event);
    }

}
