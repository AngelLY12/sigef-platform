export interface PaymentIntentFailedMetadataResponse {
  errorCode: string | null;
  declineCode: string | null;
  errorMessage: string | null;
  errorType: string | null;
  latestCharge: string;
  conceptName: string | null;
}
