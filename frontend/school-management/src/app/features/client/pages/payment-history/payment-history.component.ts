import { LoadingState } from './../../../../core/models/types/loading-state.type';
import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../shared/components/layout/page-layout/page-layout.component';
import { PaymentHistoryApiService } from '../../../../core/api/payments/students/payment-history.api.service';
import { RecordListComponent } from '../../../../shared/components/data-display/lists/record-list/record-list.component';
import {
  createPaymentHistoryParams,
  PaymentHistoryParams,
} from '../../models/payment-history/payment-history-params.model';
import { ListController } from '../../../../core/utils/list-controller.utils';
import { Paginated } from '../../../../core/utils/paginated-helper.utils';
import { PaymentHistoryResponse } from '../../models/payment-history/payment-history-response.model';
import { PaginatorComponent } from '../../../../shared/components/data-controls/paginator/paginator.component';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { Router } from '@angular/router';
import { NAVIGATION } from '../../../../core/navigation/navigation.config';
import { PaymentHistoryItemComponent } from '../../components/payment-history-item/payment-history-item.component';

@Component({
  selector: 'app-payment-history',
  standalone: true,
  imports: [
    CommonModule,
    PageLayoutComponent,
    RecordListComponent,
    PaginatorComponent,
    PaymentHistoryItemComponent
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

  openDetails(payment: PaymentHistoryResponse) {
    this.router.navigate(NAVIGATION.client.paymentDetails(payment.id));
  }
  onRefreshData() {
    const updatedParams = QueryParamsHelper.refreshData(this.paymentHistoryParams);
    this.listController.update(updatedParams);
  }
}
