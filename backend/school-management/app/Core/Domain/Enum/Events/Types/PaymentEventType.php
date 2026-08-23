<?php

namespace App\Core\Domain\Enum\Events\Types;

/**
 * @OA\Schema(
 *     schema="PaymentEventType",
 *     type="string",
 *     description="Tipo de evento generado durante el procesamiento de pagos y webhooks de Stripe.",
 *     enum={
 *         "webhook.payment_intent_succeeded",
 *         "webhook.payment_failed",
 *         "webhook.requires_action",
 *         "webhook.session_expired",
 *         "webhook.payment_cancelled",
 *         "webhook.session_completed",
 *         "webhook.session_async_completed",
 *         "webhook.charge_succeeded"
 *     },
 *     example="webhook.payment_intent_succeeded"
 * )
 */
enum PaymentEventType: string
{
    case WEBHOOK_PAYMENT_INTENT_SUCCEEDED = 'webhook.payment_intent_succeeded';
    case WEBHOOK_PAYMENT_FAILED = 'webhook.payment_failed';
    case WEBHOOK_PAYMENT_REQUIRES_ACTION = 'webhook.requires_action';
    case WEBHOOK_SESSION_EXPIRED = 'webhook.session_expired';
    case WEBHOOK_PAYMENT_CANCELLED = 'webhook.payment_cancelled';
    case WEBHOOK_SESSION_COMPLETED = 'webhook.session_completed';
    case WEBHOOK_SESSION_ASYNC_COMPLETED = 'webhook.session_async_completed';
    case WEBHOOK_CHARGE_SUCCEEDED = 'webhook.charge_succeeded';

    public function label(): string
    {
        return match ($this) {
            self::WEBHOOK_PAYMENT_INTENT_SUCCEEDED =>
            'Intento de pago completado',

            self::WEBHOOK_PAYMENT_FAILED =>
            'Pago fallido',

            self::WEBHOOK_PAYMENT_REQUIRES_ACTION =>
            'Pago requiere acción',

            self::WEBHOOK_SESSION_EXPIRED =>
            'Sesión expirada',

            self::WEBHOOK_PAYMENT_CANCELLED =>
            'Pago cancelado',

            self::WEBHOOK_SESSION_COMPLETED =>
            'Sesión de pago completada',

            self::WEBHOOK_SESSION_ASYNC_COMPLETED =>
            'Pago asíncrono completado',

            self::WEBHOOK_CHARGE_SUCCEEDED =>
            'Cargo completado',
        };
    }

}
