<?php

namespace App\Core\Domain\Entities;

use DateTime;

/**
 * @OA\Schema(
 *     schema="DomainPaymentMethod",
 *     type="object",
 *     description="Método de pago de un usuario",
 *     @OA\Property(property="id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="user_id", type="integer", example=123),
 *     @OA\Property(property="stripe_payment_method_id", type="string", example="pm_1Hh1Xx2eZvKYlo2Cj1234567"),
 *     @OA\Property(property="brand", type="string", nullable=true, example="Visa"),
 *     @OA\Property(property="last4", type="string", nullable=true, example="4242"),
 *     @OA\Property(property="exp_month", type="string", nullable=true, example="12"),
 *     @OA\Property(property="exp_year", type="string", nullable=true, example="2027")
 * )
 */
class PaymentMethod{

    public function __construct(
        /** @var User */
        public int $user_id,
        public string $stripe_payment_method_id,
        public ?string $brand = null,
        public ?string $last4 = null,
        public ?int $exp_month = null,
        public ?int $exp_year = null,
        public ?int $id = null,
    )
    {}

   public function isExpired(): bool
    {
        if (!$this->exp_month || !$this->exp_year) {
            return false;
        }

        $expiration = DateTime::createFromFormat('Y-n', "{$this->exp_year}-{$this->exp_month}");
        if (!$expiration) {
            return false;
        }

        $expiration->modify('last day of this month 23:59:59');
        $now = new DateTime();

        return $now > $expiration;
    }

   public function expirationDate(): string
    {
        if (!$this->exp_month || !$this->exp_year) {
            return 'N/A';
        }

        $yearShort = substr((string)$this->exp_year, -2);
        return sprintf('%02d/%s', $this->exp_month, $yearShort);
    }


    public function getMaskedCard(): ?string
    {
        return $this->last4 ? "**** **** **** {$this->last4}" : null;
    }
}
