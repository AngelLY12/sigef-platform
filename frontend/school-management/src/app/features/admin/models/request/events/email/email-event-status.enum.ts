export enum EmailEventStatus {
  PENDING = 'pending',
  SENT = 'sent',
  DELIVERED = 'delivered',
  FAILED = 'failed',
}

export const EmailEventStatusLabels: Record<EmailEventStatus, string> = {
  [EmailEventStatus.PENDING]: 'Pendiente',
  [EmailEventStatus.SENT]: 'Enviado',
  [EmailEventStatus.DELIVERED]: 'Entregado',
  [EmailEventStatus.FAILED]: 'Fallido',
};
