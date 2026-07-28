import { Paginated } from './../../../../core/utils/paginated-helper.utils';
import { CommonModule } from '@angular/common';
import { Component, HostListener, inject, OnInit } from '@angular/core';
import { DebtsApiService } from '../../../../core/api/financial-staff/debts.api.service';
import { ModalService } from '../../../../core/services/modal.service';
import { ListController } from '../../../../core/utils/list-controller.utils';
import {
  createDebtsListParams,
  DebtsParams,
} from '../../models/debts/debts-params.model';
import { DebtsList } from '../../models/debts/debts-list-response.model';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { TableComponent } from '../../../../shared/components/data-display/table/table.component';
import { PaginatorComponent } from '../../../../shared/components/data-display/paginator/paginator.component';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { InputComponent } from '../../../../shared/components/form/input/input.component';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { StripePaymentsComponent } from '../stripe-payments/stripe-payments.component';
import { EmptyStateComponent } from '../../../../shared/components/feedback/empty-state/empty-state.component';
import { FolderTabsComponent } from '../../../../shared/components/navigation/folder-tabs/folder-tabs.component';
import { FolderTab } from '../../../../core/models/domain/folder-tabs-config.model';
import { DEBTS_LIST_TABS } from '../../config/financial.config';
import { FilterBarComponent } from '../../../../shared/components/features/filter-bar/filter-bar.component';

@Component({
  selector: 'app-debts',
  standalone: true,
  imports: [
    CommonModule,
    PageLayoutComponent,
    TableComponent,
    InputComponent,
    ButtonComponent,
    PaginatorComponent,
    ReactiveFormsModule,
    EmptyStateComponent,
    FolderTabsComponent,
    FilterBarComponent
  ],
  templateUrl: './debts.component.html',
  styleUrl: './debts.component.scss',
})
export class DebtsComponent implements OnInit {
  private debtsService = inject(DebtsApiService);
  private listController!: ListController<DebtsParams>;
  private modalService = inject(ModalService);
  debtsListParams: DebtsParams = createDebtsListParams();
  paginatedDebts: Paginated<DebtsList> | null = null;
  debtsState: LoadingState = 'idle';
  isMobile = window.innerWidth <= 768;
  searchControl = new FormControl('');
  @HostListener('window:resize')
  onResize() {
    const isNowMobile = window.innerWidth <= 768;

    if (this.isMobile !== isNowMobile) {
      this.isMobile = isNowMobile;
    }
  }

  ngOnInit(): void {
    this.listController = new ListController<DebtsParams>(
      () => this.debtsListParams,
      (params) => (this.debtsListParams = params),
      () => this.loadDebts(),
    );
    this.loadDebts();
  }

  loadDebts() {
    this.debtsState = 'loading';
    this.debtsService.getDebts(this.debtsListParams).subscribe({
      next: (res) => {
        this.debtsState = 'success';
        this.paginatedDebts = res;
      },
      error: () => {
        this.debtsState = 'error';
      },
    });
  }

  debtsColumns = [
    { key: 'userId', label: 'ID del usuario' },
    { key: 'user_name', label: 'Nombre de usuario' },
    { key: 'n_control', label: 'Número de control' },
    { key: 'concept_name', label: 'Concepto de pago' },
    { key: 'amount', label: 'Monto' },
  ];

  debtsTabs: FolderTab[] = DEBTS_LIST_TABS;
  activeTab = 'all';

  onPageChange(newPage: number) {
    const updatedParams = QueryParamsHelper.changePage(
      this.debtsListParams,
      newPage,
    );
    this.listController.update(updatedParams);
  }

  onPageSizeChange(newSize: number) {
    const updatedParams = QueryParamsHelper.changePageSize(
      this.debtsListParams,
      newSize,
    );
    this.listController.update(updatedParams);
  }
  onResetFilters() {
    this.debtsListParams = createDebtsListParams();
    this.loadDebts();
  }
  onRefreshData() {
    const updatedParams = QueryParamsHelper.refreshData(this.debtsListParams);
    this.listController.update(updatedParams);
  }
  onSearchData() {
    const value = this.searchControl.value ?? '';

    const updatedParams = QueryParamsHelper.changeSearch(
      this.debtsListParams,
      value,
    );

    this.listController.update(updatedParams);
  }

  openStripeModal(item: DebtsList) {
    this.modalService.openCustom({
      title: `Pagos Stripe - ${item.user_name}`,
      component: StripePaymentsComponent,
      data: {
        nControl: item.n_control,
        fullName: item.user_name,
        year: new Date().getFullYear(),
      },
      onClose: (result) => {
        if (result?.refreshed) {
          this.loadDebts();
        }
      },
    });
  }
}
