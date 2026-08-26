export interface PaymentCreatedMetadataResponse {
  recipientName: string;
  conceptName: string;
  amount: string;
  createdAt: string | null;
  url: string | null;
}
