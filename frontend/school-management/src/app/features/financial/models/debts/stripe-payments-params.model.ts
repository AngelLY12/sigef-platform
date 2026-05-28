export interface StripePaymentsParams {
  search: string|null;
  year: number|null;
  forceRefresh?: boolean;
}


const BASE_STRIPE_LIST_PARAMS: Readonly<StripePaymentsParams> = {
  forceRefresh: false,
  search: null,
  year: null,
};

export function createStripeListParams(
  overrides: Partial<StripePaymentsParams> = {}
): StripePaymentsParams {
  return {
    ...BASE_STRIPE_LIST_PARAMS,
    ...overrides
  };
}
