export enum EmailEventType {
  CONCEPT_CRITICAL_AMOUNT_ALERT = 'concept_critical_amount_alert',
  CONCEPT_CREATED = 'concept_created',

  PAYMENT_CREATED = 'payment_created',
  PAYMENT_VALIDATED = 'payment_validated',
  PAYMENT_FAILED = 'payment_failed',
  PAYMENT_REQUIRES_ACTION = 'payment_requires_action',

  USER_CREATED = 'user_created',
}

export const EmailEventTypeLabels: Record<EmailEventType, string> = {
  [EmailEventType.CONCEPT_CRITICAL_AMOUNT_ALERT]: 'Alerta de monto crítico',
  [EmailEventType.CONCEPT_CREATED]: 'Concepto creado',

  [EmailEventType.PAYMENT_CREATED]: 'Pago creado',
  [EmailEventType.PAYMENT_VALIDATED]: 'Pago validado',
  [EmailEventType.PAYMENT_FAILED]: 'Pago fallido',
  [EmailEventType.PAYMENT_REQUIRES_ACTION]: 'Acción requerida para el pago',

  [EmailEventType.USER_CREATED]: 'Usuario creado',
};
