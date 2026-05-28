export interface PendingConceptsResponse {
  total_pending: TotalPending;
}

export interface TotalPending {
  totalAmount: string;
  totalCount: number;
}
