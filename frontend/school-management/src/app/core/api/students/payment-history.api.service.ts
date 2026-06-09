import { HttpClient } from "@angular/common/http";
import { inject, Injectable } from "@angular/core";
import { map, Observable } from "rxjs";
import { PaymentHistoryParams } from "../../../features/client/models/payment-history/payment-history-params.model";
import { Paginated } from "../../utils/paginated-helper.utils";
import { PaymentHistoryResponse } from "../../../features/client/models/payment-history/payment-history-response.model";
import { STUDENTS_URL } from "../../constants/api.constants";
import { ApiSuccessResponse } from "../../models/api-success-response.model";
import { PaginatedResponse } from "../../models/domain/paginated-response.model";
import { PaymentDetailsResponse } from "../../../features/client/models/payment-history/payment-details-response.model";
import { PaymentReceiptResponse } from "../../../features/client/models/payment-history/payment-receipt-response.model";

@Injectable({ providedIn: 'root' })
export class PaymentHistoryApiService {
  private http = inject(HttpClient);

  getPaymentHistory(params: PaymentHistoryParams): Observable<Paginated<PaymentHistoryResponse>> {
    const { page, perPage, forceRefresh, id } = params;
    const url = `${STUDENTS_URL.paymentsHistory}/${id ? `studentId=${id}` : ''}?page=${page}&perPage=${perPage}${forceRefresh ? `&forceRefresh=${forceRefresh}` : ''}`;
    return this.http.get<ApiSuccessResponse<{ payment_history: PaginatedResponse<PaymentHistoryResponse> }>>(
      url
    ).pipe(
      map(res => new Paginated(res.data.payment_history))
    );
  }

  getPaymentDetails(id: number): Observable<PaymentDetailsResponse> {
    return this.http.get<ApiSuccessResponse<{ payment: PaymentDetailsResponse }>>(
      `${STUDENTS_URL.paymentsHistory}/payment/${id}`
    ).pipe(
      map(res => res.data.payment)
    );
  }

  getReceipt(paymentId: number): Observable<PaymentReceiptResponse> {
    return this.http.get<ApiSuccessResponse<PaymentReceiptResponse>>(
      `${STUDENTS_URL.paymentsHistory}/receipt/${paymentId}`
    ).pipe(
      map(res => res.data)
    )
  }

}
