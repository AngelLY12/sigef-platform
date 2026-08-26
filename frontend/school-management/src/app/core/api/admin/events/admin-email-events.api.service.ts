import { HttpClient } from "@angular/common/http";
import { inject, Injectable } from "@angular/core";
import { EmailEventFilters } from "../../../../features/admin/models/request/events/email/email-event-filters.model";
import { Paginated } from "../../../utils/paginated-helper.utils";
import { map, Observable } from "rxjs";
import { EmailEventResponse } from "../../../../features/admin/models/response/events/email/email-event.response";
import { ApiSuccessResponse } from "../../../models/api/api-success-response.model";
import { PaginatedResponse } from "../../../models/domain/paginated-response.model";
import { EMAIL_EVENTS_URL } from "../../../constants/api.constants";
import { createHttpParams } from "../../../utils/params-helper.utils";
import { EmailEventHistoryFilters } from "../../../../features/admin/models/request/events/email/email-event-history-filters.model";
import { EmailEventByIdResponse } from "../../../../features/admin/models/response/events/email/email-event-by-id.response";

@Injectable({ providedIn: 'root' })
export class AdminEmailEventsApiService {
  private http = inject(HttpClient);

  getEmailEvents(
    filters: EmailEventFilters,
  ): Observable<Paginated<EmailEventResponse>> {
    return this.http
      .get<
        ApiSuccessResponse<{
          email_events: PaginatedResponse<EmailEventResponse>;
        }>
      >(EMAIL_EVENTS_URL.events, {
        params: createHttpParams(filters),
      })
      .pipe(map((res) => new Paginated(res.data.email_events)));
  }

  getUserEmailEventsHistory(
    userId: number,
    filters: EmailEventHistoryFilters,
  ): Observable<Paginated<EmailEventResponse>> {
    return this.http
      .get<
        ApiSuccessResponse<{
          email_events_history: PaginatedResponse<EmailEventResponse>;
        }>
      >(`${EMAIL_EVENTS_URL.history}/${userId}`, {
        params: createHttpParams(filters),
      })
      .pipe(map((res) => new Paginated(res.data.email_events_history)));
  }

  getEmailEventById(
    eventId: number,
    forceRefresh: boolean = false,
  ): Observable<EmailEventByIdResponse> {
    return this.http.get<
      ApiSuccessResponse<{ email_event: EmailEventByIdResponse }>
    >(`${EMAIL_EVENTS_URL.events}/${eventId}`, {
      params: createHttpParams({ forceRefresh }),
    }).pipe(
      map(res => res.data.email_event)
    );
  }
}
