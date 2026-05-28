<?php

namespace App\Core\Domain\Repositories\Query\Payments;

use App\Core\Domain\Entities\PaymentMethod;

interface PaymentMethodQueryRepInterface
{
    public function findById(int $id):?PaymentMethod;
    public function findByStripeId(string $stripeId): ?PaymentMethod;
    public function existsPaymentMethodByStripeId(string $stripeId): bool;
    public function findByStripeIds(array $stripeIds): array;
    public function getByUserId(int $userId): array;

}
