<?php

namespace App\Core\Domain\Entities;

use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Utils\Helpers\Money;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="DomainPayment",
 *     type="object",
 *     description="Representa un pago realizado por un usuario",
 *     @OA\Property(property="concept_name", type="string", nullable=true, example="Pago de inscripción"),
 *      @OA\Property(property="amount", type="string", nullable=true, example="1500"),
 *     @OA\Property(property="status", ref="#/components/schemas/PaymentStatus", example="pendiente"),
 *     @OA\Property(property="payment_method_details", type="array", nullable=true,
 *          @OA\Items(type="string"),
 *          example={"Tarjeta de crédito", "Banco XYZ"}
 *      ),
 *     @OA\Property(property="id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="user_id", type="integer", example=123),
 *     @OA\Property(property="payment_concept_id", type="integer", nullable=true, example=45),
 *     @OA\Property(property="payment_method_id", type="integer", nullable=true, example=2),
 *     @OA\Property(property="stripe_payment_method_id", type="string", nullable=true, example="pm_1Hh1Xx2eZvKYlo2Cj1234567"),
 *
 *     @OA\Property(property="amount_received", type="string", nullable=true, example="1500"),

 *     @OA\Property(property="payment_intent_id", type="string", nullable=true, example="pi_1Hh1Xx2eZvKYlo2Cd1234567"),
 *     @OA\Property(property="url", type="string", nullable=true, example="https://checkout.stripe.com/pay/cs_test_a1b2c3d4"),
 *     @OA\Property(property="stripe_session_id", type="string", nullable=true, example="cs_test_a1b2c3d4"),
 *     @OA\Property(property="created_at", nullable=true ,type="string", format="date", example="2025-09-01"),
 *     @OA\Property(property="updated_at", nullable=true ,type="string", format="date", example="2025-09-01"),
 * )
 */
class Payment
{
    public function __construct(
        public string $concept_name,
        public string $amount,
        /** @var PaymentStatus */
        public PaymentStatus $status,
        public array $payment_method_details = [],
        public ?int $id = null,
        /** @var User */
        public ?int $user_id= null,
        /** @var PaymentConcept */
        public ?int $payment_concept_id = null,
        /** @var PaymentMethod */
        public ?int $payment_method_id=null,
        public ?string $stripe_payment_method_id=null,
        public ?string $amount_received=null,
        public ?string $payment_intent_id=null,
        public ?string $url = null,
        public ?string $stripe_session_id = null,
        public ?Carbon $created_at = null,
        public ?Carbon $updated_at = null,
    ) {}

    private function money(?string $value): Money
    {
        return Money::from(($value === null || $value === '') ? '0' : $value);
    }

    public function getPendingAmount(): string
    {
        $pending = $this->money($this->amount)
            ->sub($this->money($this->amount_received));

        return $pending->isNegative()
            ? '0.00'
            : $pending->finalize();
    }

    public function getOverPaidAmount(): string
    {
        $overPaid = $this->money($this->amount_received)
            ->sub($this->money($this->amount));

        return $overPaid->isNegative()
            ? '0.00'
            : $overPaid->finalize();
    }


    public function isOverPaid(): bool
    {
        return $this->status === PaymentStatus::OVERPAID;
    }

    public function isUnderPaid():bool
    {
        return $this->status === PaymentStatus::UNDERPAID;
    }

    public function isNonPaid():bool
    {
        return in_array($this->status, PaymentStatus::nonPaidStatuses());
    }

    public function isRecentPayment(): bool
    {
        return $this->created_at?->gt(now()->subHour()) ?? false;

    }

}
