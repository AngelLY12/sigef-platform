export enum ReconciliationOutcome {
  MATCHED = 'matched',
  CORRECTED = 'corrected',
  FAILED = 'failed',
  MISMATCH = 'mismatch',
}

export const ReconciliationOutcomeLabels: Record<ReconciliationOutcome, string> = {
  [ReconciliationOutcome.MATCHED]: 'Coincidente',
  [ReconciliationOutcome.CORRECTED]: 'Corregido',
  [ReconciliationOutcome.FAILED]: 'Fallido',
  [ReconciliationOutcome.MISMATCH]: 'No coincidente',
};
