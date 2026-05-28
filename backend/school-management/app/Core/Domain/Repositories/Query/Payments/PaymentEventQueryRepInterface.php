<?php

namespace App\Core\Domain\Repositories\Query\Payments;

use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Payment\PaymentEventType;

interface PaymentEventQueryRepInterface
{
    public function findById(int $id): ?PaymentEvent;
    public function findByStripeEvent(string $stripeEventId, PaymentEventType $eventType): ?PaymentEvent;
    public function existsByStripeEvent(string $stripeEventId, PaymentEventType $eventType): bool;
    public function existsByPaymentId(int $paymentId, PaymentEventType $eventType): bool;
    public function existsByPaymentIntentId(string $paymentIntentId, PaymentEventType $eventType): bool;
    public function getPaymentsNeedingReconciliation(array $excludedOutcomes = [], int $maxRetries = 3): array;
}
