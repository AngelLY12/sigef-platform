<?php

namespace App\Core\Domain\Repositories\Stripe;

use App\Core\Domain\Entities\User;
use Stripe\PaymentIntent;

interface StripeGatewayQueryInterface
{
    public function getSetupIntentFromSession(string $sessionId);
    public function retrievePaymentMethod(string $paymentMethodId);
    public function getIntentAndCharge(string $paymentIntentId): array;
    public function getStudentPaymentsFromStripe(User $user, ?int $year): array;
    public function getPaymentIntentFromSession(string $sessionId): PaymentIntent;
    public function getBalanceFromStripe(): array;
    public function getPayoutsFromStripe(bool $onlyThisYear = false): array;
    public function getFeesFromStripe(bool $onlyThisYear = false): array;
    public function getIntentsAndChargesBatch(array $paymentIntentIds): array;
    public function countSessionsByMetadata(array $metadata, string $status): int;
    public function getSessionsByMetadata(array $metadataFilters, string $status,int $limit = 100): array;

}
