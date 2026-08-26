import { ReconciliationEventStatus } from "./reconciliation-event-status.enum";
import { ReconciliationSourceType } from "./reconciliation-source-type.enum";

export interface ReconciliationEventFilters {
  forceRefresh: boolean;
  page: number;
  perPage: number;
  paymentId: number | null;
  sourceType: ReconciliationSourceType | null;
  sourceId: string | null;
  status: ReconciliationEventStatus | null;
  from: string | null;
  to: string | null;
}

export const BASE_RECONCILIATION_EVENT_FILTERS: Readonly<ReconciliationEventFilters> = {
  forceRefresh: false,
  page: 1,
  perPage: 15,
  paymentId: null,
  sourceType: null,
  sourceId: null,
  status: null,
  from: null,
  to: null,
}
