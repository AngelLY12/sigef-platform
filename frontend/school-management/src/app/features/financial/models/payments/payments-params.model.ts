export interface PaymentsParams{
  page: number;
  perPage: number;
  forceRefresh: boolean;
  search?: string | null;
}

const BASE_PAYMENTS_LIST_PARAMS: Readonly<PaymentsParams> = {
  perPage: 15,
  page: 1,
  forceRefresh: false,
  search: null
};

export function createPaymentsListParams(
  overrides: Partial<PaymentsParams> = {}
): PaymentsParams {
  return {
    ...BASE_PAYMENTS_LIST_PARAMS,
    ...overrides
  };
}
