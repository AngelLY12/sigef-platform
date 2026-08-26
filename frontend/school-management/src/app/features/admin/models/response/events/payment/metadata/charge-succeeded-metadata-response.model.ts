export interface ChargeSucceededMetadataResponse {
  chargeId: string;
  amountCaptured: string;
  amountRefunded: string;
  paymentMethodType: string;
  receiptUrl: string | null;
  conceptName: string | null;
}
