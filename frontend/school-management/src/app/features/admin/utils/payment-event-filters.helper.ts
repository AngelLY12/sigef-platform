import { PaymentEventFilters } from '../models/request/events/payment/payment-event-filters.model';

export class PaymentEventFiltersHelper {

  static changePaymentId(
    params: PaymentEventFilters,
    paymentId: number | null
  ): PaymentEventFilters
  {
    return {
      ...params,
      paymentId: paymentId,
      page: 1
    };
  }

  static changePaymentIntentId(
    params: PaymentEventFilters,
    stripePaymentIntentId: string | null,
  ): PaymentEventFilters {
    return {
      ...params,
      stripePaymentIntentId: stripePaymentIntentId?.trim() || null,
      page: 1,
    };
  }

  static changeSessionId(
    params: PaymentEventFilters,
    stripeSessionId: string | null,
  ): PaymentEventFilters {
    return {
      ...params,
      stripeSessionId: stripeSessionId?.trim() || null,
      page: 1,
    };
  }

  static changeProcessed(
    params: PaymentEventFilters,
    processed: boolean | null,
  ): PaymentEventFilters {
    return {
      ...params,
      processed: processed ?? null,
      page: 1,
    };
  }
}
