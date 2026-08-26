import { ReconciliationSourceType, ReconciliationSourceTypeLabels } from './../../../../models/request/events/reconciliation/reconciliation-source-type.enum';
import { Component, inject, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../../../shared/components/layout/page-layout/page-layout.component';
import { TableComponent } from '../../../../../../shared/components/data-display/tables/table/table.component';
import { FolderTabsComponent } from '../../../../../../shared/components/navigation/folder-tabs/folder-tabs.component';
import { PaginatorComponent } from '../../../../../../shared/components/data-controls/paginator/paginator.component';
import { FilterBarComponent } from '../../../../../../shared/components/data-controls/filter-bar/filter-bar.component';
import { SelectComponent } from '../../../../../../shared/components/form/controls/select/select.component';
import { InputComponent } from '../../../../../../shared/components/form/controls/input/input.component';
import { ButtonComponent } from '../../../../../../shared/components/ui/button/button.component';
import { Router } from '@angular/router';
import { AdminReconciliationEventsApiService } from '../../../../../../core/api/admin/events/admin-reconciliation-events.api.service';
import { ListController } from '../../../../../../core/utils/list-controller.utils';
import { BASE_RECONCILIATION_EVENT_FILTERS, ReconciliationEventFilters } from '../../../../models/request/events/reconciliation/reconciliation-event-filters.model';
import { ModalService } from '../../../../../../core/services/modal.service';
import { LoadingState } from '../../../../../../core/models/types/loading-state.type';
import { Paginated } from '../../../../../../core/utils/paginated-helper.utils';
import { ReconcileEventResponse } from '../../../../models/response/events/reconciliation/reconcile-event.response';
import { createParams } from '../../../../../../core/utils/params-helper.utils';
import { FolderTab } from '../../../../../../shared/components/navigation/folder-tabs/folder-tabs-config.model';
import { RECONCILIATION_EVENT_TABS, RECONCILIATION_EVENTS_COLUMNS } from '../../../../config/tabs.config';
import { TableColumn } from '../../../../../../shared/components/data-display/tables/table/table-column.model';
import { QueryParamsHelper } from '../../../../../../core/utils/query-params-helper.utils';
import { ReconciliationEventFiltersHelper } from '../../../../utils/reconciliation-event-filters.helper';
import { ReconciliationEventsTimelineComponent } from '../reconciliation-events-timeline/reconciliation-events-timeline.component';
import { NAVIGATION } from '../../../../../../core/navigation/navigation.config';
import { SelectOption } from '../../../../../../shared/components/form/controls/select/select-option.config.model';
import { enumToOptionsWithLabel } from '../../../../../../core/utils/enum-helper.utils';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-reconciliation-events',
  standalone: true,
  imports: [
    PageLayoutComponent,
    TableComponent,
    FolderTabsComponent,
    PaginatorComponent,
    FilterBarComponent,
    SelectComponent,
    InputComponent,
    ButtonComponent,
    FormsModule
  ],
  templateUrl: './reconciliation-events.component.html',
  styleUrl: './reconciliation-events.component.scss',
})
export class ReconciliationEventsComponent implements OnInit {
  private paymentEventsApi = inject(AdminReconciliationEventsApiService);
  private router = inject(Router);
  private listController!: ListController<ReconciliationEventFilters>;
  private modalService = inject(ModalService);
  eventsState: LoadingState = 'idle';
  paginatedEvents: Paginated<ReconcileEventResponse> | null = null;
  paymentFilters = createParams(BASE_RECONCILIATION_EVENT_FILTERS);
  activePaymentTab = '';
  readonly reconciliationTabs: FolderTab[] = RECONCILIATION_EVENT_TABS;

  sourceId: string | null = null;
  paymentId: number  | null = null;

  reconcileColumns: TableColumn[] = RECONCILIATION_EVENTS_COLUMNS;

  sourceTypeOptions: SelectOption[] = enumToOptionsWithLabel(ReconciliationSourceType, ReconciliationSourceTypeLabels);

  ngOnInit(): void {
    this.listController = new ListController<ReconciliationEventFilters>(
      () => this.paymentFilters,
      (params) => (this.paymentFilters = params),
      () => this.loadEvents(),
    );

    this.loadEvents();
  }

  loadEvents(): void {
    this.eventsState = 'loading';
    this.paymentEventsApi.getReconciliationEvents(this.paymentFilters).subscribe({
      next: (res) => {
        this.paginatedEvents = res;
        this.eventsState = 'success';
      },
      error: (error) => {
        if (error.status === 422) {
          this.eventsState = 'success';
          return;
        }

        this.eventsState = 'error';
      },
    });
  }

  onPaymentTabChange(status: string): void {
    this.activePaymentTab = status;
    const updatedParams = QueryParamsHelper.changeStatus(
      this.paymentFilters,
      status || null,
    );
    this.listController.update(updatedParams);
  }


  onPageChange(newPage: number) {
    const updatedParams = QueryParamsHelper.changePage(
      this.paymentFilters,
      newPage,
    );
    this.listController.update(updatedParams);
  }

  onPageSizeChange(newSize: number) {
    const updatedParams = QueryParamsHelper.changePageSize(
      this.paymentFilters,
      newSize,
    );
    this.listController.update(updatedParams);
  }
  onResetFilters() {
    this.paymentFilters = createParams(BASE_RECONCILIATION_EVENT_FILTERS);
    this.sourceId = null;
    this.paymentId = null;
    this.loadEvents();
  }

  onRefreshData() {
    const updatedParams = QueryParamsHelper.refreshData(this.paymentFilters);
    this.listController.update(updatedParams);
  }

  onFromChange(from: string) {
    const updatedParams = QueryParamsHelper.changeFromRange(
      this.paymentFilters,
      from,
    );
    this.listController.update(updatedParams);
  }

  onToChange(to: string) {
    const updatedParams = QueryParamsHelper.changeToRange(
      this.paymentFilters,
      to,
    );
    this.listController.update(updatedParams);
  }

  onSourceTypeChange(sourceType: string): void
  {
    const updatedParams = QueryParamsHelper.changeSourceType(
      this.paymentFilters,
      sourceType
    );
    this.listController.update(updatedParams);
  }

  onSearchByPaymentId(): void {
    const paymentId = this.paymentId|| null;
    if (paymentId === null) {
      return;
    }

    const updatedParams = ReconciliationEventFiltersHelper.changePaymentId(
      this.paymentFilters,
      paymentId,
    );

    if (
      updatedParams.paymentId ===
      this.paymentFilters.paymentId
    ) {
      return;
    }

    this.listController.update(updatedParams);
  }

  onSearchBySourceId(): void {
    const sourceId = this.sourceId?.trim() || null;

    if (sourceId === null) {
      return;
    }
    const updatedParams = ReconciliationEventFiltersHelper.changeSourceId(
      this.paymentFilters,
      sourceId,
    );

    if (updatedParams.sourceId === this.paymentFilters.sourceId) {
      return;
    }

    this.listController.update(updatedParams);
  }

  onNavigateToHistory(paymentId: number) {
    this.modalService.openCustom({
      title: `Pago #${paymentId} - Historial`,
      component: ReconciliationEventsTimelineComponent,
      data: {
        paymentId: paymentId,
      },
    });
  }
  onNavigateToDetails(eventId: number) {
    this.router.navigate(NAVIGATION.admin.reconciliationEventDetails(eventId));
  }
}
