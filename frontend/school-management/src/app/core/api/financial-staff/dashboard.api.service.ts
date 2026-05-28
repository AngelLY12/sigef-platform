import { HttpClient } from "@angular/common/http";
import { inject, Injectable } from "@angular/core";
import { DashboardParams } from "../../../features/financial/models/dashboard/dashboard-params.model";
import { map, Observable } from "rxjs";
import { TotalPending } from "../../../features/client/models/dashboard/pending-concepts-response.model";
import { ApiSuccessResponse } from "../../models/api-success-response.model";
import { PendingConceptsResponse } from "../../../features/financial/models/dashboard/pendig-concept.response.model";
import { FINANCIAL_STAFF_URLS } from "../../constants/api.constants";
import { StudentsSummaryResponse, TotalStudents } from "../../../features/financial/models/dashboard/students-summary.response.model";
import { PaymentsData, PaymentsResponse } from "../../../features/financial/models/dashboard/payments.response.model";
import { Paginated } from "../../utils/paginated-helper.utils";
import { ConceptsHistoryItems } from "../../../features/financial/models/dashboard/concepts-history.response.model";
import { PaginatedResponse } from "../../models/domain/paginated-response.model";
import { Payout, PayoutResponse } from "../../../features/financial/models/dashboard/payout-response.model";

@Injectable({ providedIn: 'root' })
export class DashboardService {
  private http = inject(HttpClient);


  getPending(params?: DashboardParams): Observable<TotalPending> {
    return this.http.get<ApiSuccessResponse<PendingConceptsResponse>>(`${FINANCIAL_STAFF_URLS.dashboard}/pending`)
    .pipe(
      map(res => res.data.total_pending)
    );
  }

  getStudents(params?: DashboardParams): Observable<TotalStudents> {
    return this.http.get<ApiSuccessResponse<StudentsSummaryResponse>>(`${FINANCIAL_STAFF_URLS.dashboard}/students`)
    .pipe(
      map(res => res.data.total_students)
    );
  }

  getPayments(params?: DashboardParams): Observable<PaymentsData> {
    return this.http.get<ApiSuccessResponse<PaymentsResponse>>(`${FINANCIAL_STAFF_URLS.dashboard}/payments`)
    .pipe(
      map(res => res.data.payments_data)
    );
  }

  getConceptsHistory(
    params?: DashboardParams & { page?: number; perPage?: number }
  ): Observable<Paginated<ConceptsHistoryItems>> {
    return this.http.get
    <ApiSuccessResponse<{ concepts: PaginatedResponse<ConceptsHistoryItems> }>>(
      `${FINANCIAL_STAFF_URLS.dashboard}/concepts`
    ).pipe(
      map(res => new Paginated(res.data.concepts))
    );
  }

  refreshDashboard(): Observable<string> {
    return this.http.post<ApiSuccessResponse<string>>(`${FINANCIAL_STAFF_URLS.dashboard}/refresh`, null)
    .pipe(
      map(res => res.message)
    );
  }

  createPayout(): Observable<Payout> {
    return this.http.post<ApiSuccessResponse<PayoutResponse>>(`${FINANCIAL_STAFF_URLS.dashboard}/payout`, null)
    .pipe(
      map(res => res.data.payout)
    );
  }
}
