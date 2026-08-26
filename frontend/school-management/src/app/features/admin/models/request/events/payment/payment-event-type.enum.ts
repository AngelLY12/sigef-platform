export enum PaymentEventType {
  WEBHOOK_PAYMENT_INTENT_SUCCEEDED = 'webhook.payment_intent_succeeded',
  WEBHOOK_PAYMENT_FAILED = 'webhook.payment_failed',
  WEBHOOK_PAYMENT_REQUIRES_ACTION = 'webhook.requires_action',
  WEBHOOK_SESSION_EXPIRED = 'webhook.session_expired',
  WEBHOOK_PAYMENT_CANCELLED = 'webhook.payment_cancelled',
  WEBHOOK_SESSION_COMPLETED = 'webhook.session_completed',
  WEBHOOK_SESSION_ASYNC_COMPLETED = 'webhook.session_async_completed',
  WEBHOOK_CHARGE_SUCCEEDED = 'webhook.charge_succeeded',
}

export const PaymentEventTypeLabels: Record<PaymentEventType, string> = {
  [PaymentEventType.WEBHOOK_PAYMENT_INTENT_SUCCEEDED]:
    'Intento de pago completado',

  [PaymentEventType.WEBHOOK_PAYMENT_FAILED]:
    'Pago fallido',

  [PaymentEventType.WEBHOOK_PAYMENT_REQUIRES_ACTION]:
    'Pago requiere acción',

  [PaymentEventType.WEBHOOK_SESSION_EXPIRED]:
    'Sesión expirada',

  [PaymentEventType.WEBHOOK_PAYMENT_CANCELLED]:
    'Pago cancelado',

  [PaymentEventType.WEBHOOK_SESSION_COMPLETED]:
    'Sesión de pago completada',

  [PaymentEventType.WEBHOOK_SESSION_ASYNC_COMPLETED]:
    'Pago asíncrono completado',

  [PaymentEventType.WEBHOOK_CHARGE_SUCCEEDED]:
    'Cargo completado',
};
