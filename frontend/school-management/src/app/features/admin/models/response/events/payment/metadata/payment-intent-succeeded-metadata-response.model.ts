export interface PaymentIntentSucceededMetadataResponse {
  latestCharge: string;
  intentStatus: string;
  conceptName: string | null;
}
