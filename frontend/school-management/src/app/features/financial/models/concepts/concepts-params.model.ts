import { PaymentConceptStatus } from "../../../../core/models/enums/payment-concepts-status.enum";

export interface ConceptsParams {
  status: PaymentConceptStatus|null;
  perPage: number;
  page: number;
  forceRefresh?: boolean;
}

const BASE_CONCEPTS_LIST_PARAMS: Readonly<ConceptsParams> = {
  perPage: 15,
  page: 1,
  forceRefresh: false,
  status: null
};

export function createConceptsListParams(
  overrides: Partial<ConceptsParams> = {}
): ConceptsParams {
  return {
    ...BASE_CONCEPTS_LIST_PARAMS,
    ...overrides
  };
}
