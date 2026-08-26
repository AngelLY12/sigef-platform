export enum ReconciliationSourceType {
  MANUAL = 'manual',
  SYSTEM = 'system',
}

export const ReconciliationSourceTypeLabels: Record<ReconciliationSourceType, string> = {
  [ReconciliationSourceType.MANUAL]: 'Manual',
  [ReconciliationSourceType.SYSTEM]: 'Sistema',
};
