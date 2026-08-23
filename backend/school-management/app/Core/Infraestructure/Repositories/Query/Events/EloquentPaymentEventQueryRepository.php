<?php

namespace App\Core\Infraestructure\Repositories\Query\Events;

use App\Core\Application\DTO\Request\Events\PaymentEvent\PaymentEventFilters;
use App\Core\Application\DTO\Response\Events\PaymentEvent\PaymentEventResponse;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Query\Events\PaymentEventQueryRepInterface;
use App\Core\Infraestructure\Mappers\PaymentEventMapper;
use App\Models\Payment;
use App\Models\PaymentEvent as EloquentPaymentEvent;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use function Laravel\Prompts\select;

class EloquentPaymentEventQueryRepository implements PaymentEventQueryRepInterface
{
    public function findById(int $id): ?PaymentEvent
    {
        $eloquent = EloquentPaymentEvent::find($id);
        return $eloquent ? PaymentEventMapper::toDomain($eloquent) : null;
    }
    public function findByStripeEvent(string $stripeEventId, PaymentEventType $eventType): ?PaymentEvent
    {
        $eloquent = EloquentPaymentEvent::where('stripe_event_id', $stripeEventId)
            ->where('event_type', $eventType)
            ->first();

        return $eloquent ? PaymentEventMapper::toDomain($eloquent) : null;
    }
    public function findByPaymentAndEventTypes(int $paymentId, array $eventTypes): Collection
    {
        return EloquentPaymentEvent::query()
            ->where('payment_id', $paymentId)
            ->whereIn('event_type', $eventTypes)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursor()
            ->map(
                fn (EloquentPaymentEvent $event) =>
                PaymentEventMapper::toDomain($event)
            )
            ->collect();
    }

    public function getAllPaymentEvents(PaymentEventFilters $filters): LengthAwarePaginator
    {
        Log::info('Prueba que no deberia salir',[
            'processed' => $filters->processed,
            'type' => get_debug_type($filters->processed),
        ]);
        return EloquentPaymentEvent::query()
            ->select([
                'payment_events.id',
                'payment_events.payment_id',
                'payment_events.event_type',
                'payment_events.processed',
                'payment_events.created_at',
                'payments.concept_name',
            ])
            ->leftJoin(
                'payments',
                'payments.id',
                '=',
                'payment_events.payment_id'
            )
            ->when(
                $filters->paymentId,
                fn ($query, $paymentId) => $query->where('payment_events.payment_id', $paymentId)
            )
            ->when(
                $filters->eventType,
                fn ($query, $eventType) => $query->where('payment_events.event_type', $eventType)
            )
            ->when(
                $filters->processed !== null,
                fn ($query) =>
                $query->where('payment_events.processed', $filters->processed)
            )
            ->when(
                $filters->stripePaymentIntentId,
                fn ($query, $stripePaymentIntentId) =>
                $query->where(
                    'payment_events.stripe_payment_intent_id',
                    $stripePaymentIntentId
                )
            )
            ->when(
                $filters->stripeSessionId,
                fn ($query, $stripeSessionId) =>
                $query->where(
                    'payment_events.stripe_session_id',
                    $stripeSessionId
                )
            )
            ->when(
                $filters->from,
                fn ($query, $from) =>
                $query->where('payment_events.created_at', '>=', $from)
            )
            ->when(
                $filters->to,
                fn ($query, $to) =>
                $query->where('payment_events.created_at', '<=', $to)
            )
            ->orderByDesc('payment_events.created_at')
            ->orderByDesc('payment_events.id')
            ->paginate(
                perPage: $filters->perPage,
                page: $filters->page,
            )
            ->through(
                fn (EloquentPaymentEvent $event) => PaymentEventResponse::create($event)
            )
            ;
    }

    public function getPaymentTimeline(int $paymentId): Collection
    {

        return EloquentPaymentEvent::query()
            ->select([
                'payment_events.id',
                'payment_events.payment_id',
                'payment_events.event_type',
                'payment_events.processed',
                'payment_events.created_at',
                'payments.concept_name',
            ])
            ->leftJoin(
                'payments',
                'payments.id',
                '=',
                'payment_events.payment_id'
            )
            ->where('payment_events.payment_id', $paymentId)
            ->orderByDesc('payment_events.created_at')
            ->orderByDesc('payment_events.id')
            ->get()
            ->map(
                fn (EloquentPaymentEvent $event) =>
                PaymentEventResponse::create($event)
            );
    }


}
