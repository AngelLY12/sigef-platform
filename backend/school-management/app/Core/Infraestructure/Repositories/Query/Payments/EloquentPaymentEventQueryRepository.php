<?php

namespace App\Core\Infraestructure\Repositories\Query\Payments;

use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Infraestructure\Mappers\PaymentEventMapper;
use App\Models\Payment;
use App\Models\PaymentEvent as EloquentPaymentEvent;
use App\Core\Domain\Repositories\Query\Payments\PaymentEventQueryRepInterface;

class EloquentPaymentEventQueryRepository implements PaymentEventQueryRepInterface
{
    public function findById(int $id): ?PaymentEvent
    {
        $eloquent = EloquentPaymentEvent::find($id);
        return $eloquent ? PaymentEventMapper::toDomain($eloquent) : null;
    }

    public function findByPaymentId(int $paymentId): ?PaymentEvent
    {
        $eloquent = EloquentPaymentEvent::where('payment_id', $paymentId)->first();
        return $eloquent ? PaymentEventMapper::toDomain($eloquent) : null;
    }

    public function findAllEventsByPaymentId(int $paymentId): array
    {
         return EloquentPaymentEvent::where('payment_id', $paymentId)
             ->orderByDesc('created_at')
            ->get()
            ->map(fn($event) => PaymentEventMapper::toDomain($event))
            ->toArray();
    }

    public function findByPaymentIntentId(string $paymentIntentId): ?PaymentEvent
    {
        $eloquent = EloquentPaymentEvent::where('stripe_payment_intent_id', $paymentIntentId)
            ->first();
        return $eloquent ? PaymentEventMapper::toDomain($eloquent) : null;
    }
    public function findByStripeSessionId(string $stripeSessionId): ?PaymentEvent
    {
        $eloquent = EloquentPaymentEvent::where('stripe_session_id', $stripeSessionId)
            ->first();
        return $eloquent ? PaymentEventMapper::toDomain($eloquent) : null;
    }
    public function findByStripeEvent(string $stripeEventId, PaymentEventType $eventType): ?PaymentEvent
    {
        $eloquent = EloquentPaymentEvent::where('stripe_event_id', $stripeEventId)
            ->where('event_type', $eventType)
            ->first();

        return $eloquent ? PaymentEventMapper::toDomain($eloquent) : null;
    }

    public function findPendingEvents(int $hours = 24, int $maxRetries = 3): array
    {
        return EloquentPaymentEvent::where('processed', false)
            ->where('retry_count', '<', $maxRetries)
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($event) => PaymentEventMapper::toDomain($event))
            ->toArray();
    }

    public function existsByStripeEvent(string $stripeEventId, PaymentEventType $eventType): bool
    {
        return EloquentPaymentEvent::where('stripe_event_id', $stripeEventId)
            ->where('event_type', $eventType)
            ->exists();
    }

    public function existsByPaymentId(int $paymentId, PaymentEventType $eventType): bool
    {
        return EloquentPaymentEvent::where('payment_id', $paymentId)
            ->where('event_type', $eventType)
            ->exists();
    }

    public function existsByPaymentIntentId(string $paymentIntentId, PaymentEventType $eventType): bool
    {
        return EloquentPaymentEvent::where('stripe_payment_intent_id', $paymentIntentId)
            ->where('event_type', $eventType)
            ->exists();
    }


    public function findRecentReconciliationEvent(int $paymentId, \DateTime $since): ?PaymentEvent
    {
        $eloquent = EloquentPaymentEvent::where('payment_id', $paymentId)
            ->whereIn('event_type', [
                PaymentEventType::RECONCILIATION_COMPLETED->value,
            ])
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->first();

        return $eloquent ? PaymentEventMapper::toDomain($eloquent) : null;
    }

    public function findReconciliationEventsByPayment(int $paymentId): array
    {
        return EloquentPaymentEvent::where('payment_id', $paymentId)
            ->whereIn('event_type', [
                PaymentEventType::RECONCILIATION_STARTED->value,
                PaymentEventType::RECONCILIATION_COMPLETED->value,
                PaymentEventType::RECONCILIATION_FAILED->value,
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($event) => PaymentEventMapper::toDomain($event))
            ->toArray();
    }

    public function getPaymentsNeedingReconciliation(array $excludedOutcomes = [], int $maxRetries = 3): array
    {
        return Payment::whereHas('paymentEvents', function ($query) {
            $query->whereNotNull('payment_id');
        })
            ->where(function($query) {
                $query->whereDoesntHave('paymentEvents', function ($query2) {
                    $query2->where('event_type', PaymentEventType::RECONCILIATION_COMPLETED->value);
                })
                    ->orWhereHas('paymentEvents', function ($query2) {
                        $query2->where('event_type', PaymentEventType::RECONCILIATION_COMPLETED->value)
                            ->where('created_at', '<', now()->subHours(2))
                            ->latest()
                            ->limit(1);
                    });
            })

            ->whereIn('status', PaymentStatus::reconcilableStatuses())
            ->whereNotNull('payment_intent_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->pluck('id')
            ->toArray();
    }


}
