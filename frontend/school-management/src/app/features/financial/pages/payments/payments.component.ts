import { Paginated } from './../../../../core/utils/paginated-helper.utils';
import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { SelectComponent } from '../../../../shared/components/form/select/select.component';
import { PaymentsView } from '../../models/payments/payments-view.enum';
import { FormControl, FormsModule, ReactiveFormsModule } from '@angular/forms';
import { PaymentsTableComponent } from '../../components/payments/payments-table/payments-table.component';
import { PaymentsResponse } from '../../models/payments/payments-response.model';
import { PaymentsByConceptResponse } from '../../models/payments/payments-by-concept-response.model';
import { PaymentsByStudentResponse } from '../../models/payments/payments-by-student-response.model';
import { PaginatorComponent } from '../../../../shared/components/data-display/paginator/paginator.component';
import { PaymentsByConceptTableComponent } from '../../components/payments/payments-by-concept-table/payments-by-concept-table.component';
import { PaymentsByStudentTableComponent } from '../../components/payments/payments-by-student-table/payments-by-student-table.component';
import { PaymentsApiService } from '../../../../core/api/financial-staff/payments.api.service';
import { ModalService } from '../../../../core/services/modal.service';
import { Router } from '@angular/router';
import { ListController } from '../../../../core/utils/list-controller.utils';
import {
  createPaymentsListParams,
  PaymentsParams,
} from '../../models/payments/payments-params.model';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { Observable } from 'rxjs';
import { FilterBarComponent } from '../../../../shared/components/features/filter-bar/filter-bar.component';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { InputComponent } from '../../../../shared/components/form/input/input.component';
import { FolderTab } from '../../../../core/models/domain/folder-tabs-config.model';
import { FolderTabsComponent } from '../../../../shared/components/navigation/folder-tabs/folder-tabs.component';

@Component({
  selector: 'app-payments',
  standalone: true,
  imports: [
    CommonModule,
    PageLayoutComponent,
    PaginatorComponent,
    FilterBarComponent,
    InputComponent,
    FormsModule,
    ReactiveFormsModule,
    PaymentsTableComponent,
    PaymentsByConceptTableComponent,
    PaymentsByStudentTableComponent,
    FolderTabsComponent,
  ],
  templateUrl: './payments.component.html',
  styleUrl: './payments.component.scss',
})
export class PaymentsComponent implements OnInit {
  private paymentsService = inject(PaymentsApiService);
  private modalService = inject(ModalService);
  private router = inject(Router);
  private listController!: ListController<PaymentsParams>;

  paymentsListParams: PaymentsParams = createPaymentsListParams();
  state: LoadingState = 'idle';
  searchControl = new FormControl('');
  currentView = PaymentsView.Payments;
  PaymentsView = PaymentsView;
  paginatedData:
    | Paginated<PaymentsResponse>
    | Paginated<PaymentsByConceptResponse>
    | Paginated<PaymentsByStudentResponse>
    | null = null;

  ngOnInit(): void {
    this.listController = new ListController<PaymentsParams>(
      () => this.paymentsListParams,
      (params) => (this.paymentsListParams = params),
      () => this.loadData(),
    );
    this.loadData();
  }

  readonly paymentTabs: FolderTab[] = [
    {
      id: PaymentsView.Payments,
      label: 'Pagos',
      icon: 'payments',
    },
    {
      id: PaymentsView.ByConcept,
      label: 'Por concepto',
      icon: 'receipt_long',
    },
    {
      id: PaymentsView.ByStudent,
      label: 'Por alumno',
      icon: 'school',
    },
  ];

  loadData(): void {
    this.state = 'loading';

    let request$: Observable<
      | Paginated<PaymentsResponse>
      | Paginated<PaymentsByConceptResponse>
      | Paginated<PaymentsByStudentResponse>
    >;

    switch (this.currentView) {
      case PaymentsView.Payments:
        request$ = this.paymentsService.getPayments(this.paymentsListParams);
        break;

      case PaymentsView.ByConcept:
        request$ = this.paymentsService.getPaymentsByConcept(
          this.paymentsListParams,
        );
        break;

      case PaymentsView.ByStudent:
        request$ = this.paymentsService.getPaymentsByStudent(
          this.paymentsListParams,
        );
        break;
    }

    request$.subscribe({
      next: (res) => {
        this.paginatedData = res as
          | Paginated<PaymentsResponse>
          | Paginated<PaymentsByConceptResponse>
          | Paginated<PaymentsByStudentResponse>;

        this.state = 'success';
      },
      error: () => {
        this.state = 'error';
      },
    });
  }

  get placeholder(): string {
    switch (this.currentView) {
      case PaymentsView.Payments:
        return 'Concepto, email o nombre del estudiante';

      case PaymentsView.ByConcept:
        return 'Nombre del concepto';

      case PaymentsView.ByStudent:
        return 'CURP, email o número de control';
    }
  }

  get paymentsData(): PaymentsResponse[] {
    return (this.paginatedData?.data?.items as PaymentsResponse[]) ?? [];
  }

  get paymentsByConceptData(): PaymentsByConceptResponse[] {
    return (
      (this.paginatedData?.data?.items as PaymentsByConceptResponse[]) ?? []
    );
  }

  get paymentsByStudentData(): PaymentsByStudentResponse[] {
    return (
      (this.paginatedData?.data?.items as PaymentsByStudentResponse[]) ?? []
    );
  }

  onViewChange(view: string): void {
    this.currentView = view as PaymentsView;

    this.paymentsListParams = QueryParamsHelper.changePage(
      this.paymentsListParams,
      1,
    );

    this.loadData();
  }

  onPageChange(newPage: number) {
    const updatedParams = QueryParamsHelper.changePage(
      this.paymentsListParams,
      newPage,
    );
    this.listController.update(updatedParams);
  }

  onPageSizeChange(newSize: number) {
    const updatedParams = QueryParamsHelper.changePageSize(
      this.paymentsListParams,
      newSize,
    );
    this.listController.update(updatedParams);
  }
  onResetFilters() {
    this.paymentsListParams = createPaymentsListParams();
    this.loadData();
  }

  onSearchData() {
    const value = this.searchControl.value ?? '';

    const updatedParams = QueryParamsHelper.changeSearch(
      this.paymentsListParams,
      value,
    );

    this.listController.update(updatedParams);
  }

  onRefreshData() {
    const updatedParams = QueryParamsHelper.refreshData(
      this.paymentsListParams,
    );
    this.listController.update(updatedParams);
  }
}
