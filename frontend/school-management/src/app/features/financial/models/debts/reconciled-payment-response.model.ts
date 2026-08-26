export interface ReconciledPaymentResponse {
  paymentId: number;
  reconciled: boolean;
  source: string;
  changes: any[]
}
