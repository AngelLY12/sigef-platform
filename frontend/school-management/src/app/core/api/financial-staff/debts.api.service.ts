import { StripePaymentsParams } from './../../../features/financial/models/debts/stripe-payments-params.model';
import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { DebtsParams } from '../../../features/financial/models/debts/debts-params.model';
import { Paginated } from '../../utils/paginated-helper.utils';
import { DebtsList } from '../../../features/financial/models/debts/debts-list-response.model';
import { FINANCIAL_STAFF_URLS } from '../../constants/api.constants';
import { ApiSuccessResponse } from '../../models/api-success-response.model';
import { PaginatedResponse } from '../../models/domain/paginated-response.model';
import { StripePaymentsResponse } from '../../../features/financial/models/debts/stripe-payments-response.model';

@Injectable({ providedIn: 'root' })
export class DebtsApiService {
  private http = inject(HttpClient);

  getDebts(params: DebtsParams): Observable<Paginated<DebtsList>> {
    const { page, perPage, search, forceRefresh } = params;
    const url = `${FINANCIAL_STAFF_URLS.debts}?page=${page}&perPage=${perPage}${search ? `&search=${search}` : ''}${forceRefresh ? `&forceRefresh=${forceRefresh}` : ''}`;
    return this.http
      .get<
        ApiSuccessResponse<{ pending_payments: PaginatedResponse<DebtsList> }>
      >(url)
      .pipe(map((res) => new Paginated(res.data.pending_payments)));
  }

  getStripePayments(
    params: StripePaymentsParams,
  ): Observable<StripePaymentsResponse[]> {
    const { year, search, forceRefresh } = params;
    const url = `${FINANCIAL_STAFF_URLS.debts}/stripe-payments?${search ? `search=${search}` : ''}${year ? `&year=${year}` : ''}${forceRefresh ? `&forceRefresh=${forceRefresh}` : ''}`;
    return this.http.get<ApiSuccessResponse<{ payments: StripePaymentsResponse[] }>>(
      url
    ).pipe(
      map((res) => res.data.payments)
    );
  }
}
