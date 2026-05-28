import { PaginatedResponse } from './../../models/domain/paginated-response.model';
import { ApiSuccessResponse } from './../../models/api-success-response.model';
import { HttpClient } from "@angular/common/http";
import { inject, Injectable } from "@angular/core";
import { map, Observable } from "rxjs";
import { DashboardParams } from "../../../features/client/models/dashboard/dashboard-params.model";
import { PendingConceptsResponse, TotalPending } from "../../../features/client/models/dashboard/pending-concepts-response.model";
import { OverdueResponse } from '../../../features/client/models/dashboard/overdue-concepts-response.model';
import { PaidData, PaidResponse } from '../../../features/client/models/dashboard/paid-concepts-response.model';
import { Paginated } from '../../utils/paginated-helper.utils';
import { PaymentHistoryItem } from '../../../features/client/models/dashboard/payment-history-response.model';
import { STUDENTS_URL } from '../../constants/api.constants';

@Injectable({ providedIn: 'root' })
export class DashboardService {
  private http = inject(HttpClient);

  getPending(params?: DashboardParams): Observable<TotalPending>
  {
    return this.http.get<ApiSuccessResponse<PendingConceptsResponse>>(`${STUDENTS_URL.dashboard}/pending`)
    .pipe(
      map(res => res.data.totalPending)
    );
  }
  getOverdue(params?: DashboardParams): Observable<TotalPending> {
    return this.http.get<ApiSuccessResponse<OverdueResponse>>(`${STUDENTS_URL.dashboard}/overdue`)
    .pipe(
      map(res => res.data.total_overdue)
    );
  }
  getPaid(params?: DashboardParams): Observable<PaidData> {
    return this.http.get<ApiSuccessResponse<PaidResponse>>(
      `${STUDENTS_URL.dashboard}/paid/`
    ).pipe(
      map(res => res.data.paid_data)
    );
  }

  getHistory(
    params?: DashboardParams & { page?: number; perPage?: number }
  ): Observable<Paginated<PaymentHistoryItem>> {
    return this.http.get<ApiSuccessResponse<{ payment_history: PaginatedResponse<PaymentHistoryItem>} >>(
      `${STUDENTS_URL.dashboard}/history`
    ).pipe(
      map(res =>  new Paginated(res.data.payment_history))
    );
  }

}
