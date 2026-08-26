export enum ReconciliationEventStatus {
  PENDING = 'pending',
  COMPLETED = 'completed',
  FAILED = 'failed',
}

export const ReconciliationEventStatusLabels: Record<ReconciliationEventStatus, string> = {
  [ReconciliationEventStatus.PENDING]: 'Pendiente',
  [ReconciliationEventStatus.COMPLETED]: 'Completada',
  [ReconciliationEventStatus.FAILED]: 'Fallida',
};
