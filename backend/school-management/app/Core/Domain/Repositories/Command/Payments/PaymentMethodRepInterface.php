<?php

namespace App\Core\Domain\Repositories\Command\Payments;

use App\Core\Domain\Entities\PaymentMethod;
use App\Core\Domain\Entities\User;

interface PaymentMethodRepInterface{
    public function create(PaymentMethod $paymentMethod):PaymentMethod;
    public function updateByStripeId(string $stripeId, array $fields): int;
    public function delete(int $paymentMethodId):void;
    public function deleteByStripeId(string $stripeId): bool;
}
