export interface ReconcileEventResponse {
  id: number;
  paymentId: number | null;
  conceptName: string | null;
  status: string;
  sourceType: string | null;
  sourceId: string | null;
  createdAt: string;
}

