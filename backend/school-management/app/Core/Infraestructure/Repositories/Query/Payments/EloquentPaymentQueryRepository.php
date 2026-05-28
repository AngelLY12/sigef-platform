<?php

namespace App\Core\Infraestructure\Repositories\Query\Payments;

use App\Core\Application\DTO\Response\Payment\PaymentToDisplay;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Application\Mappers\PaymentMapper as MappersPaymentMapper;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Utils\Helpers\Money;
use App\Core\Infraestructure\Mappers\PaymentMapper;
use App\Models\Payment as EloquentPayment;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EloquentPaymentQueryRepository implements PaymentQueryRepInterface
{

    public function findById(int $id): ?Payment
    {
        return optional(EloquentPayment::find($id), fn($pc) => PaymentMapper::toDomain($pc));
    }

    public function findByIds(array $ids): Collection
    {
        if(empty($ids)) return collect();
        return EloquentPayment::whereIn('id', $ids)
            ->lazy(200)
            ->map(fn($pc) => PaymentMapper::toDomain($pc))
            ->collect();
    }

    public function findByIdToDisplay(int $id): ?PaymentToDisplay
    {
        $userId = Auth::id();
        $payment = EloquentPayment::where('id',$id)->where('user_id',$userId)->first();
        if(empty($payment)) return null;
        return MappersPaymentMapper::toPaymentToDisplay($payment);
    }

    public function findBySessionId(string $sessionId): ?Payment
    {
        $payment= EloquentPayment::where('stripe_session_id', $sessionId)->first();
        return $payment ? PaymentMapper::toDomain($payment) : null;
    }

    public function findByIntentId(string $intentId): ?Payment
    {
        $payment=EloquentPayment::where('payment_intent_id', $intentId)->first();
        return $payment ? PaymentMapper::toDomain($payment) : null;
    }

    private function getMonthlyAggregation(Builder $query): array
    {
        $results = $query->selectRaw("
            YEAR(created_at) as year,
            MONTH(created_at) as month,
            SUM(amount_received) as month_total
        ")
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $total = Money::from('0');
        foreach ($results as $row) {
            $total=$total->add($row->month_total);
        }

        return [
            'total' => $total->finalize(),
            'by_month' => $results
                ->mapWithKeys(fn ($row) => [
                    sprintf('%04d-%02d', $row->year, $row->month)
                    => Money::from($row->month_total)->finalize()
                ])
                ->toArray()
        ];
    }

    public function sumPaymentsByUserYear(int $userId, bool $onlyThisYear): array
    {
        $query = EloquentPayment::where('user_id', $userId)
            ->whereNotNull('amount_received');

        if ($onlyThisYear) {
            $query->whereBetween('created_at', [
                now()->startOfYear(),
                now()->endOfYear()
            ]);
        }

        return $this->getMonthlyAggregation($query);
    }

    public function getAllPaymentsMade(bool $onlyThisYear): array
    {
        $query = EloquentPayment::query()->whereNotNull('amount_received');

        if ($onlyThisYear) {
            $query->whereBetween('created_at', [
                now()->startOfYear(),
                now()->endOfYear()
            ]);
        }

        return $this->getMonthlyAggregation($query);
    }

    public function getPaymentHistory(int $userId, int $perPage, int $page, bool $onlyThisYear): LengthAwarePaginator
    {
         $query= EloquentPayment::where('user_id', $userId)
        ->select('id', 'concept_name', 'amount', 'amount_received', 'status' ,'created_at');
        if ($onlyThisYear) {
            $query->whereYear('created_at', now()->year);
        }
        return $query->orderBy('created_at','desc')
        ->paginate($perPage, ['*'], 'page', $page)
        ->through(fn($p) => MappersPaymentMapper::toHistoryResponse($p));
    }

    public function getPaymentHistoryWithDetails(int $userId, int $perPage, int $page): LengthAwarePaginator
    {
        return EloquentPayment::where('user_id', $userId)
            ->select('id', 'concept_name', 'amount', 'amount_received','status','created_at')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($p) => MappersPaymentMapper::toHistoryResponse($p));
    }

    public function findByIntentOrSession(int $userId, string $paymentIntentId): ?Payment
    {
        $payment=EloquentPayment::
            where('user_id', $userId)
            ->where(function ($q) use ($paymentIntentId) {
                $q->where('payment_intent_id', $paymentIntentId)
                  ->orWhere('stripe_session_id', $paymentIntentId);
            })
            ->first();
        return $payment ? PaymentMapper::toDomain($payment):null;
    }

    public function getAllWithSearchEager(?string $search, int $perPage, int $page): LengthAwarePaginator
    {
        return EloquentPayment::with([
            'user:id,name,last_name',
        ])
            ->select('id', 'user_id', 'concept_name', 'amount', 'amount_received', 'payment_method_details', 'created_at')
            ->latest('payments.created_at')
            ->when($search, function ($q) use ($search) {
            $q->whereHas('user', fn($sub) =>
                $sub->where('name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
            )->orWhere('concept_name', 'like', "%$search%");

        })
        ->paginate($perPage, ['*'], 'page', $page)
        ->through(fn($p) => MappersPaymentMapper::toListItemResponse($p));
    }


     /**
     * @return Generator<int, Payment>
     */
    public function getReconciliablePaymentsCursor(): Generator
    {
        foreach (EloquentPayment::whereIn('status', PaymentStatus::reconcilableStatuses())
                     ->whereNotNull('payment_intent_id')
                     ->where('created_at', '>=', now()->subMonth())
                     ->cursor() as $model) {
            yield PaymentMapper::toDomain($model);
        }
    }

    public function getLastPaymentForConcept(int $userId, int $conceptId, array $allowedStatuses = []): ?Payment
    {
        $query = EloquentPayment::query()
            ->where('user_id', $userId)
            ->where('payment_concept_id', $conceptId);

        if (!empty($allowedStatuses)) {
            $query->whereIn('status', $allowedStatuses);
        }
        $payment = $query->orderByDesc('id')->first();

        return $payment ? PaymentMapper::toDomain($payment) : null;
    }

    public function getPaymentsByConceptName(int $perPage, int $page, ?string $search=null): LengthAwarePaginator
    {
        $query = EloquentPayment::query()
            ->when($search, function ($q) use ($search) {
                $q->where('concept_name', 'LIKE', "%{$search}%");
            });
        return $query
            ->select([
                'concept_name',
                DB::raw('SUM(amount) as amount_total'),
                DB::raw('SUM(amount_received) as amount_received_total'),
                DB::raw('DATE_FORMAT(MIN(created_at), "%Y-%m-%d") as first_payment_date'),
                DB::raw('DATE_FORMAT(MAX(created_at), "%Y-%m-%d") as last_payment_date')
            ])
            ->groupBy('concept_name')
            ->orderBy('last_payment_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($item) => MappersPaymentMapper::toPaymentsMadeByConceptName($item));
    }
}
