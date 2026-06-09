export interface PaymentHistoryParams {
  page: number;
  perPage: number;
  forceRefresh?: boolean;
  id: number | null;
}

const BASE_PAYMENT_HISTORY_PARAMS: Readonly<PaymentHistoryParams> = {
  page: 1,
  perPage: 15,
  forceRefresh: false,
  id: null
};

export function createPaymentHistoryParams(
  overrides: Partial<PaymentHistoryParams> = {}
): PaymentHistoryParams {
  return {
    ...BASE_PAYMENT_HISTORY_PARAMS,
    ...overrides
  };
}
