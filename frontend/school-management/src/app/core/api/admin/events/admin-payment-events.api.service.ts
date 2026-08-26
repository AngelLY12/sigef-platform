import { HttpClient } from "@angular/common/http";
import { inject, Injectable } from "@angular/core";
import { PaymentEventFilters } from "../../../../features/admin/models/request/events/payment/payment-event-filters.model";
import { map, Observable } from "rxjs";
import { Paginated } from "../../../utils/paginated-helper.utils";
import { PaymentEventResponse } from "../../../../features/admin/models/response/events/payment/payment-event.response";
import { ApiSuccessResponse } from "../../../models/api/api-success-response.model";
import { PaginatedResponse } from "../../../models/domain/paginated-response.model";
import { PAYMENT_EVENTS_URL } from "../../../constants/api.constants";
import { createHttpParams } from "../../../utils/params-helper.utils";
import { PaymentEventByIdResponse } from "../../../../features/admin/models/response/events/payment/payment-event-by-id.response";

@Injectable({ providedIn: 'root' })
export class AdminPaymentEventsApiService {

  private http = inject(HttpClient);

  getPaymentEvents(filters: PaymentEventFilters): Observable<Paginated<PaymentEventResponse>>
  {
    return this.http.get<ApiSuccessResponse<{payment_events: PaginatedResponse<PaymentEventResponse> }>>(
      PAYMENT_EVENTS_URL.events,
      {
        params: createHttpParams(filters)
      }
    ).pipe(
      map(res => new Paginated(res.data.payment_events))
    );
  }

  getPaymentEventsTimeline(paymentId: number, forceRefresh: boolean): Observable<PaymentEventResponse[]>
  {
    return this.http.get<ApiSuccessResponse<{ payment_events_timeline: PaymentEventResponse[] }>>(
      `${PAYMENT_EVENTS_URL.history}/${paymentId}`,
      {
        params: createHttpParams({ forceRefresh })
      }
    ).pipe(
      map(res => res.data.payment_events_timeline)
    );
  }

  getPaymentEventById(eventId: number, forceRefresh: boolean): Observable<PaymentEventByIdResponse>
  {
    return this.http.get<ApiSuccessResponse<{ payment_event: PaymentEventByIdResponse}>>(
      `${PAYMENT_EVENTS_URL.events}/${eventId}`,
      {
        params: createHttpParams({ forceRefresh })
      }
    ).pipe(
      map(res => res.data.payment_event)
    );
  }
}
