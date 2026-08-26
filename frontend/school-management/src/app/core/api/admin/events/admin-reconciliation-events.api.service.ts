import { HttpClient } from "@angular/common/http";
import { inject, Injectable } from "@angular/core";
import { ReconciliationEventFilters } from "../../../../features/admin/models/request/events/reconciliation/reconciliation-event-filters.model";
import { map, Observable } from "rxjs";
import { Paginated } from "../../../utils/paginated-helper.utils";
import { ReconcileEventResponse } from "../../../../features/admin/models/response/events/reconciliation/reconcile-event.response";
import { PaginatedResponse } from "../../../models/domain/paginated-response.model";
import { ApiSuccessResponse } from "../../../models/api/api-success-response.model";
import { RECONCILE_EVENTS_URL } from "../../../constants/api.constants";
import { createHttpParams } from "../../../utils/params-helper.utils";
import { ReconcileEventByIdResponse } from "../../../../features/admin/models/response/events/reconciliation/reconcile-event-by-id.response";

@Injectable({ providedIn: 'root' })
export class AdminReconciliationEventsApiService {
  private http = inject(HttpClient);


  getReconciliationEvents(filters: ReconciliationEventFilters): Observable<Paginated<ReconcileEventResponse>>
  {
    return this.http.get<ApiSuccessResponse<{ reconcile_events: PaginatedResponse<ReconcileEventResponse> }>>(
      RECONCILE_EVENTS_URL.events,
      {
        params: createHttpParams(filters)
      }
    ).pipe(
      map(res => new Paginated(res.data.reconcile_events))
    )
  }

  getReconciliationTimeline(paymentId: number, forceRefresh: boolean): Observable<ReconcileEventResponse[]>
  {
    return this.http.get<ApiSuccessResponse<{ reconcile_events_timeline: ReconcileEventResponse[] }>>(
      `${RECONCILE_EVENTS_URL.history}/${paymentId}`,
      {params: createHttpParams({ forceRefresh })}
    ).pipe(
      map(res => res.data.reconcile_events_timeline)
    );
  }

  getReconciliationEventById(eventId: number, forceRefresh: boolean): Observable<ReconcileEventByIdResponse>
  {
    return this.http.get<ApiSuccessResponse<{ reconcile_event: ReconcileEventByIdResponse}>>(
      `${RECONCILE_EVENTS_URL.events}/${eventId}`,
      {params: createHttpParams({ forceRefresh })}
    ).pipe(
      map(res => res.data.reconcile_event)
    );
  }

}
