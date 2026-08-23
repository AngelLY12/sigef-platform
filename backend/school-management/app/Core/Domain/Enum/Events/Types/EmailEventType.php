<?php

namespace App\Core\Domain\Enum\Events\Types;

/**
 * @OA\Schema(
 *     schema="EmailEventType",
 *     type="string",
 *     description="Tipo de evento relacionado con el envío y procesamiento de correos.",
 *     enum={
 *         "concept_critical_amount_alert",
 *         "concept_created",
 *         "payment_created",
 *         "payment_validated",
 *         "payment_failed",
 *         "payment_requires_action",
 *         "user_created"
 *     },
 *     example="payment_validated"
 * )
 */
enum EmailEventType: string
{
    // Concepts
    case CONCEPT_CRITICAL_AMOUNT_ALERT = 'concept_critical_amount_alert';
    case CONCEPT_CREATED = 'concept_created';

    // Payments
    case PAYMENT_CREATED = 'payment_created';
    case PAYMENT_VALIDATED = 'payment_validated';
    case PAYMENT_FAILED = 'payment_failed';
    case PAYMENT_REQUIRES_ACTION = 'payment_requires_action';

    // Users
    case USER_CREATED = 'user_created';

    public function label(): string
    {
        return match ($this) {
            self::CONCEPT_CRITICAL_AMOUNT_ALERT => 'Alerta de monto crítico',
            self::CONCEPT_CREATED => 'Concepto creado',

            self::PAYMENT_CREATED => 'Pago creado',
            self::PAYMENT_VALIDATED => 'Pago validado',
            self::PAYMENT_FAILED => 'Pago fallido',
            self::PAYMENT_REQUIRES_ACTION => 'Acción requerida para el pago',

            self::USER_CREATED => 'Usuario creado',
        };
    }

}
