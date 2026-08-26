import { PaymentStatus } from "../../../../../../core/models/enums/payment-status.enum";
import { PaymentEventType } from "../../../request/events/payment/payment-event-type.enum";
import { PaymentEventMetadataResponse } from "./metadata/payment-event-metadata-response.model";

export interface PaymentEventByIdResponse {
  id: number;
  paymentId: number;
  stripeEventId: string | null;
  stripePaymentIntentId: string | null;
  stripeSessionId: string | null;
  eventType: PaymentEventType;
  eventTypeLabel: string;
  metadata: PaymentEventMetadataResponse | null;
  amountReceived: string | null;
  status: PaymentStatus | null;
  statusLabel: string | null;
  processed: boolean;
  errorMessage: string | null;
  retryCount: number;
  processedAt: string | null;
  createdAt: string | null;
  updatedAt: string | null;
}
