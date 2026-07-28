export interface ValidatePaymentResponse {
  metadata: PaymentMetadata;
}

export interface PaymentMetadata {
  wasCreated: boolean;
  wasReconciled: boolean;
  message: string;
  reconciliationResult: {
    processed: number;
    updated: number;
    notified: number;
    failed: number;
  };
}
