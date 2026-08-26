import { PaymentEventType } from "./payment-event-type.enum";

export interface PaymentEventFilters {
  forceRefresh: boolean;
  page: number;
  perPage: number;
  paymentId: number | null;
  eventType: PaymentEventType | null;
  processed: boolean | null;
  stripePaymentIntentId: string | null;
  stripeSessionId: string | null;
  from: string | null;
  to: string | null;
}


export const BASE_PAYMENT_EVENT_FILTERS: Readonly<PaymentEventFilters> = {
  forceRefresh: false,
  page: 1,
  perPage: 15,
  paymentId: null,
  eventType: null,
  processed: null,
  stripePaymentIntentId:null,
  stripeSessionId: null,
  from: null,
  to: null,
};
