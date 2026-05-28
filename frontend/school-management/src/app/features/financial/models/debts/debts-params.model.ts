export interface DebtsParams {
  search?: string|null;
  perPage: number;
  page: number;
  forceRefresh?: boolean;
}


const BASE_DEBTS_LIST_PARAMS: Readonly<DebtsParams> = {
  perPage: 15,
  page: 1,
  forceRefresh: false,
  search: null
};

export function createDebtsListParams(
  overrides: Partial<DebtsParams> = {}
): DebtsParams {
  return {
    ...BASE_DEBTS_LIST_PARAMS,
    ...overrides
  };
}
