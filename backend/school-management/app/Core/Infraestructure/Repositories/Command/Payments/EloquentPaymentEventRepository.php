<?php

namespace App\Core\Infraestructure\Repositories\Command\Payments;

use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Repositories\Command\Payments\PaymentEventRepInterface;
use App\Core\Infraestructure\Mappers\PaymentEventMapper;
use App\Models\PaymentEvent as EloquentPaymentEvent;

class EloquentPaymentEventRepository implements PaymentEventRepInterface
{
    public function create(PaymentEvent $event): PaymentEvent
    {
        $eloquent = EloquentPaymentEvent::create(
            PaymentEventMapper::toEloquent($event)
        );

        return PaymentEventMapper::toDomain($eloquent);
    }
    public function update(int $paymentEventId, array $fields): PaymentEvent
    {
        $paymentEvent = EloquentPaymentEvent::findOrFail($paymentEventId);
        $paymentEvent->update($fields);
        $paymentEvent->refresh();
        return PaymentEventMapper::toDomain($paymentEvent);
    }

    public function deleteOlderEvents(): int
    {
        $count = 0;
        EloquentPaymentEvent::where('created_at', '<', now()->subMonths(3))
            ->chunkById(1000, function($logs) use (&$count) {
                $count += $logs->count();
                $logs->each->delete();
            });
        return $count;
    }

}
