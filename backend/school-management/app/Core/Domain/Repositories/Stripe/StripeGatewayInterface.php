<?php

namespace App\Core\Domain\Repositories\Stripe;

use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\User;
use Stripe\Checkout\Session;

interface StripeGatewayInterface{
    public function createStripeUser(User $user):string;
    public function createSetupSession(string $customerId):Session;
    public function createCheckoutSession(string $customerId, PaymentConcept $paymentConcept, string $amount, int $userId):Session;
    public function deletePaymentMethod(string $paymentMethodId):bool;
    public function expireSessionIfPending(string $sessionId): bool;
    public function createPayout(): array;

}
