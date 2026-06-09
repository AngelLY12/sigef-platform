import { PaymentsParams } from './../../../features/financial/models/payments/payments-params.model';
import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { Paginated } from '../../utils/paginated-helper.utils';
import { FINANCIAL_STAFF_URLS } from '../../constants/api.constants';
import { ApiSuccessResponse } from '../../models/api-success-response.model';
import { PaginatedResponse } from '../../models/domain/paginated-response.model';
import { PaymentsByConceptResponse } from '../../../features/financial/models/payments/payments-by-concept-response.model';
import { PaymentsByStudentResponse } from '../../../features/financial/models/payments/payments-by-student-response.model';
import { PaymentsResponse } from '../../../features/financial/models/payments/payments-response.model';

@Injectable({ providedIn: 'root' })
export class PaymentsApiService {
  private http = inject(HttpClient);

  getPayments(params: PaymentsParams) {
    return this.getPaginated<PaymentsResponse>(
      FINANCIAL_STAFF_URLS.payments,
      params
    );
  }

  getPaymentsByConcept(params: PaymentsParams) {
    return this.getPaginated<PaymentsByConceptResponse>(
      `${FINANCIAL_STAFF_URLS.payments}/by-concept`,
      params
    );
  }

  getPaymentsByStudent(params: PaymentsParams) {
    return this.getPaginated<PaymentsByStudentResponse>(
      `${FINANCIAL_STAFF_URLS.payments}/students`,
      params
    );
  }

  private getPaginated<T>(
    endpoint: string,
    params: PaymentsParams,
  ): Observable<Paginated<T>> {
    const url = this.buildUrl(endpoint, params);

    return this.http
      .get<ApiSuccessResponse<{ payments: PaginatedResponse<T> }>>(url)
      .pipe(map((res) => new Paginated(res.data.payments)));
  }

  private buildUrl(endpoint: string, params: PaymentsParams): string {
    const { page, perPage, forceRefresh, search } = params;

    return `${endpoint}?page=${page}&perPage=${perPage}${
      search ? `&search=${search}` : ''
    }${forceRefresh ? `&forceRefresh=${forceRefresh}` : ''}`;
  }
}
