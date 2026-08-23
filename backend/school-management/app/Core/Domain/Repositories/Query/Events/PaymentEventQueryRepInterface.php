<?php

namespace App\Core\Domain\Repositories\Query\Events;

use App\Core\Application\DTO\Request\Events\PaymentEvent\PaymentEventFilters;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PaymentEventQueryRepInterface
{
    public function findById(int $id): ?PaymentEvent;
    public function findByStripeEvent(string $stripeEventId, PaymentEventType $eventType): ?PaymentEvent;
    public function findByPaymentAndEventTypes(int $paymentId, array $eventTypes): Collection;
    public function getAllPaymentEvents(PaymentEventFilters $filters): LengthAwarePaginator;
    public function getPaymentTimeline(
        int $paymentId,
    ): Collection;
}
