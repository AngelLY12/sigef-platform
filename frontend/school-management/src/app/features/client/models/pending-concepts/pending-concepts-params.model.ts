export interface PendingConceptsParams {
  forceRefresh?: boolean;
  id: number | null;
}


const BASE_CONCEPTS_LIST_PARAMS: Readonly<PendingConceptsParams> = {
  forceRefresh: false,
  id: null
};

export function createPendingConceptsListParams(
  overrides: Partial<PendingConceptsParams> = {}
): PendingConceptsParams {
  return {
    ...BASE_CONCEPTS_LIST_PARAMS,
    ...overrides
  };
}
