export interface PaymentValidatedMetadataResponse {
  recipientName: string;
  conceptName: string;
  amount: string;
  amountReceived: string;
  paymentMethodType: string | null;
  status: string;
  url: string | null;
}
