export interface PendingConceptsResponse {
  totalPending: TotalPending;
}

export interface TotalPending {
  totalAmount: string;
  totalCount: number;
}
