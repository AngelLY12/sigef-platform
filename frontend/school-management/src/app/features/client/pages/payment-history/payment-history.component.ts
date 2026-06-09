import { LoadingState } from './../../../../core/models/types/loading-state.type';
import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { PaymentHistoryApiService } from '../../../../core/api/students/payment-history.api.service';
import { RecordListComponent } from '../../../../shared/components/data-display/record-list/record-list.component';
import {
  createPaymentHistoryParams,
  PaymentHistoryParams,
} from '../../models/payment-history/payment-history-params.model';
import { ListController } from '../../../../core/utils/list-controller.utils';
import { Paginated } from '../../../../core/utils/paginated-helper.utils';
import { PaymentHistoryResponse } from '../../models/payment-history/payment-history-response.model';
import { PaginatorComponent } from '../../../../shared/components/data-display/paginator/paginator.component';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { CurrencyMXNPipe } from '../../../../shared/pipes/currency-mxn.pipe';
import { Router } from '@angular/router';
import { NAVIGATION } from '../../../../core/navigation/navigation.config';

@Component({
  selector: 'app-payment-history',
  standalone: true,
  imports: [
    CommonModule,
    PageLayoutComponent,
    RecordListComponent,
    PaginatorComponent,
    CurrencyMXNPipe
  ],
  templateUrl: './payment-history.component.html',
  styleUrl: './payment-history.component.scss',
})
export class PaymentHistoryComponent implements OnInit {
  private paymentHistoryService = inject(PaymentHistoryApiService);
  private router = inject(Router);

  loading: LoadingState = 'idle';
  paymentHistoryParams: PaymentHistoryParams = createPaymentHistoryParams();
  paginatedHistory: Paginated<PaymentHistoryResponse> | null = null;
  listController!: ListController<PaymentHistoryParams>;
  ngOnInit(): void {
    this.listController = new ListController<PaymentHistoryParams>(
      () => this.paymentHistoryParams,
      (params) => (this.paymentHistoryParams = params),
      () => this.loadPaymentHistory(),
    );

    this.loadPaymentHistory();
  }

  loadPaymentHistory() {
    this.loading = 'loading';
    this.paymentHistoryService
      .getPaymentHistory(this.paymentHistoryParams)
      .subscribe({
        next: (res) => {
          this.paginatedHistory = res;
          this.loading = 'success';
        },
        error: () => {
          this.loading = 'error';
        },
      });
  }

  onPageChange(newPage: number) {
    const updatedParams = QueryParamsHelper.changePage(
      this.paymentHistoryParams,
      newPage,
    );
    this.listController.update(updatedParams);
  }

  onPageSizeChange(newSize: number) {
    const updatedParams = QueryParamsHelper.changePageSize(
      this.paymentHistoryParams,
      newSize,
    );
    this.listController.update(updatedParams);
  }

  getStatusClass(status: string): string {
    const map: Record<string, string> = {
      Pagado: 'status-paid',
      Completado: 'status-paid',
      Pendiente: 'status-pending',
      'No pagado': 'status-pending',
      'Requiere acción': 'status-action',
      'Pago parcial': 'status-action',
      Sobrepago: 'status-action',
      Fallido: 'status-failed',
    };

    return map[status] ?? 'status-pending';
  }

  openDetails(payment: PaymentHistoryResponse) {
    this.router.navigate(NAVIGATION.client.paymentDetails(payment.id));
  }
}
