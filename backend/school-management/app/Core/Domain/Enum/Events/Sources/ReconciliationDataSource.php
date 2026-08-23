<?php

namespace App\Core\Domain\Enum\Events\Sources;

/**
 * @OA\Schema(
 *     schema="ReconciliationDataSource",
 *     type="string",
 *     description="Origen del evento de reconciliacion desde stripe.",
 *     enum={
 *         "checkout_session",
 *         "payment_intent",
 *         "charge",
 *     },
 *     example="charge"
 * )
 *
 */
enum ReconciliationDataSource: string
{
    case CHECKOUT_SESSION = 'checkout_session';
    case PAYMENT_INTENT = 'payment_intent';
    case CHARGE = 'charge';

    public function label(): string
    {
        return match($this) {
          self::CHECKOUT_SESSION => 'Sesión de Checkout',
          self::PAYMENT_INTENT => 'Intención de pago',
          self::CHARGE => 'Cargo',
        };
    }

}
