export interface ReconciliationCorrectedMetadataResponse {
  dataSource: string;
  amountReceived: string;
  paymentIntentId: string | null;
  chargeId: string | null;
}
