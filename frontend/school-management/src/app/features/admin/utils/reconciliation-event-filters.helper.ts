import { ReconciliationEventFilters } from "../models/request/events/reconciliation/reconciliation-event-filters.model";

export class ReconciliationEventFiltersHelper {

  static changePaymentId(
    params: ReconciliationEventFilters,
    paymentId: number | null
  ): ReconciliationEventFilters
  {
    return {
      ...params,
      paymentId: paymentId,
      page: 1
    };
  }

  static changeSourceId(
    params: ReconciliationEventFilters,
    sourceId: string | null
  ): ReconciliationEventFilters
  {
    return {
      ...params,
      sourceId: sourceId,
      page: 1
    };
  }

}
