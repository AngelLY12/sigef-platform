export enum EmailEventSourceType {
  STRIPE = 'stripe',
  USER = 'user',
  CONCEPT = 'concept',
  SYSTEM = 'system',
}

export const EmailEventSourceTypeLabels: Record<EmailEventSourceType, string> = {
  [EmailEventSourceType.STRIPE]: 'Pasarela de pago',
  [EmailEventSourceType.USER]: 'Usuario',
  [EmailEventSourceType.CONCEPT]: 'Concepto',
  [EmailEventSourceType.SYSTEM]: 'Sistema',
};
