<?php

namespace App\Core\Infraestructure\Repositories\Query\Payments;

use App\Core\Domain\Entities\PaymentMethod;
use App\Models\PaymentMethod as EloquentPaymentMethod;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Infraestructure\Mappers\PaymentMethodMapper;

class EloquentPaymentMethodQueryRepository implements PaymentMethodQueryRepInterface
{
    public function findById(int $id): ?PaymentMethod
    {
        return optional(EloquentPaymentMethod::find($id), fn($pc) => PaymentMethodMapper::toDomain($pc));
    }

    public function findByStripeId(string $stripeId): ?PaymentMethod
    {
        return optional(EloquentPaymentMethod::where('stripe_payment_method_id', $stripeId)->first(), fn($pm) => PaymentMethodMapper::toDomain($pm));
    }

    public function existsPaymentMethodByStripeId(string $stripeId): bool
    {
        return EloquentPaymentMethod::where('stripe_payment_method_id', $stripeId)->exists();
    }

    public function findByStripeIds(array $stripeIds): array
    {
        if (empty($stripeIds)) {
            return [];
        }

        $models = EloquentPaymentMethod::whereIn('stripe_payment_method_id', $stripeIds)->get();

        return $models->map(fn($model) => PaymentMethodMapper::toDomain($model))->toArray();
    }

    public function getByUserId(int $userId): array
    {
        $methods = EloquentPaymentMethod::where('user_id', $userId)
            ->latest('created_at')
            ->get();
        return $methods->map(fn($pm) => PaymentMethodMapper::toDomain($pm))->toArray();
    }
}
