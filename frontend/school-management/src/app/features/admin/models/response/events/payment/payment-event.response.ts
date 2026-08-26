import { PaymentEventType } from "../../../request/events/payment/payment-event-type.enum";

export interface PaymentEventResponse {
  id: number;
  paymentId: number | null;
  conceptName: string | null;
  eventType: PaymentEventType;
  processed: boolean;
  createdAt: string;
}
