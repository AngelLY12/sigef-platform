import { Component, inject, OnInit } from '@angular/core';
import { AdminPaymentEventsApiService } from '../../../../../../core/api/admin/events/admin-payment-events.api.service';
import { Router } from '@angular/router';
import { ListController } from '../../../../../../core/utils/list-controller.utils';
import {
  BASE_PAYMENT_EVENT_FILTERS,
  PaymentEventFilters,
} from '../../../../models/request/events/payment/payment-event-filters.model';
import { LoadingState } from '../../../../../../core/models/types/loading-state.type';
import { Paginated } from '../../../../../../core/utils/paginated-helper.utils';
import { PaymentEventResponse } from '../../../../models/response/events/payment/payment-event.response';
import { createParams } from '../../../../../../core/utils/params-helper.utils';
import { FolderTab } from '../../../../../../shared/components/navigation/folder-tabs/folder-tabs-config.model';
import {
  PAYMENT_EVENT_TABS,
  PAYMENT_EVENT_TYPES_BY_TAB,
  PAYMENT_EVENTS_COLUMNS,
  PaymentEventTab,
} from '../../../../config/tabs.config';
import { TableColumn } from '../../../../../../shared/components/data-display/tables/table/table-column.model';
import { QueryParamsHelper } from '../../../../../../core/utils/query-params-helper.utils';
import { NAVIGATION } from '../../../../../../core/navigation/navigation.config';
import { PaymentEventFiltersHelper } from '../../../../utils/payment-event-filters.helper';
import {
  PaymentEventType,
  PaymentEventTypeLabels,
} from '../../../../models/request/events/payment/payment-event-type.enum';
import { SelectOption } from '../../../../../../shared/components/form/controls/select/select-option.config.model';
import { CommonModule } from '@angular/common';
import { FolderTabsComponent } from '../../../../../../shared/components/navigation/folder-tabs/folder-tabs.component';
import { TableComponent } from '../../../../../../shared/components/data-display/tables/table/table.component';
import { PaginatorComponent } from '../../../../../../shared/components/data-controls/paginator/paginator.component';
import { PageLayoutComponent } from '../../../../../../shared/components/layout/page-layout/page-layout.component';
import { FilterBarComponent } from '../../../../../../shared/components/data-controls/filter-bar/filter-bar.component';
import { SelectComponent } from '../../../../../../shared/components/form/controls/select/select.component';
import { InputComponent } from '../../../../../../shared/components/form/controls/input/input.component';
import { ButtonComponent } from '../../../../../../shared/components/ui/button/button.component';
import { FormsModule } from '@angular/forms';
import { ModalService } from '../../../../../../core/services/modal.service';
import { PaymentEventsTimelineComponent } from '../payment-events-timeline/payment-events-timeline.component';

@Component({
  selector: 'app-payment-events',
  standalone: true,
  imports: [
    CommonModule,
    FolderTabsComponent,
    TableComponent,
    PaginatorComponent,
    PageLayoutComponent,
    FilterBarComponent,
    SelectComponent,
    InputComponent,
    ButtonComponent,
    FormsModule,
  ],
  templateUrl: './payment-events.component.html',
  styleUrl: './payment-events.component.scss',
})
export class PaymentEventsComponent implements OnInit {
  private paymentEventsApi = inject(AdminPaymentEventsApiService);
  private router = inject(Router);
  private listController!: ListController<PaymentEventFilters>;
  private modalService = inject(ModalService);
  eventsState: LoadingState = 'idle';
  paginatedEvents: Paginated<PaymentEventResponse> | null = null;
  paymentFilters = createParams(BASE_PAYMENT_EVENT_FILTERS);
  activePaymentTab = '';
  readonly paymentTabs: FolderTab[] = PAYMENT_EVENT_TABS;

  stripePaymentIntentId: string | null = null;
  stripeSessionId: string | null = null;
  processed: boolean | null = null;
  paymentId: number  | null = null;

  paymentColumns: TableColumn[] = PAYMENT_EVENTS_COLUMNS;

  get availableEventTypes(): SelectOption[] {
    const eventTypes = this.activePaymentTab
      ? PAYMENT_EVENT_TYPES_BY_TAB[this.activePaymentTab as PaymentEventTab]
      : Object.values(PaymentEventType);

    return eventTypes.map((value) => ({
      label: PaymentEventTypeLabels[value],
      value,
    }));
  }

  readonly processedOptions: SelectOption[] = [
    {
      label: 'Todos',
      value: '',
    },
    {
      label: 'Procesados',
      value: 'true',
    },
    {
      label: 'No procesados',
      value: 'false',
    },
  ];

  ngOnInit(): void {
    this.listController = new ListController<PaymentEventFilters>(
      () => this.paymentFilters,
      (params) => (this.paymentFilters = params),
      () => this.loadEvents(),
    );

    this.loadEvents();
  }

  loadEvents(): void {
    this.eventsState = 'loading';
    this.paymentEventsApi.getPaymentEvents(this.paymentFilters).subscribe({
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

  onPaymentTabChange(tab: string): void {
    this.activePaymentTab = tab;
    if (!tab) {
      const updatedParams = QueryParamsHelper.changeEventType(
        this.paymentFilters,
        null,
      );

      this.listController.update(updatedParams);
      return;
    }
    const eventTypes = PAYMENT_EVENT_TYPES_BY_TAB[tab as PaymentEventTab];

    const defaultEventType = eventTypes[0];

    const updatedParams = QueryParamsHelper.changeEventType(
      this.paymentFilters,
      defaultEventType,
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
    this.paymentFilters = createParams(BASE_PAYMENT_EVENT_FILTERS);
    this.stripePaymentIntentId = null;
    this.stripeSessionId = null;
    this.processed = null;
    this.paymentId = null;
    this.loadEvents();
  }

  onRefreshData() {
    const updatedParams = QueryParamsHelper.refreshData(this.paymentFilters);
    this.listController.update(updatedParams);
  }

  onEventTypeChange(eventType: string) {
    const updatedParams = QueryParamsHelper.changeEventType(
      this.paymentFilters,
      eventType,
    );
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

  onSearchByPaymentIntentId(): void {
    const stripePaymentIntentId = this.stripePaymentIntentId?.trim() || null;
    if (stripePaymentIntentId === null) {
      return;
    }

    const updatedParams = PaymentEventFiltersHelper.changePaymentIntentId(
      this.paymentFilters,
      stripePaymentIntentId,
    );

    if (
      updatedParams.stripePaymentIntentId ===
      this.paymentFilters.stripePaymentIntentId
    ) {
      return;
    }

    this.listController.update(updatedParams);
  }

  onSearchByPaymentId(): void {
    const paymentId = this.paymentId|| null;
    if (paymentId === null) {
      return;
    }

    const updatedParams = PaymentEventFiltersHelper.changePaymentId(
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

  onSearchBySessionId(): void {
    const stripeSessionId = this.stripeSessionId?.trim() || null;

    if (stripeSessionId === null) {
      return;
    }
    const updatedParams = PaymentEventFiltersHelper.changeSessionId(
      this.paymentFilters,
      stripeSessionId,
    );

    if (updatedParams.stripeSessionId === this.paymentFilters.stripeSessionId) {
      return;
    }

    this.listController.update(updatedParams);
  }

  onChangeProcessed(value: string): void {
    const processed = value === '' ? null : value === 'true';

    const updatedParams = PaymentEventFiltersHelper.changeProcessed(
      this.paymentFilters,
      processed,
    );

    if (updatedParams.processed === this.paymentFilters.processed) {
      return;
    }

    this.listController.update(updatedParams);
  }

  onNavigateToHistory(paymentId: number) {
    this.modalService.openCustom({
      title: `Pago #${paymentId} - Historial`,
      component: PaymentEventsTimelineComponent,
      data: {
        paymentId: paymentId,
      },
    });
  }
  onNavigateToDetails(eventId: number) {
    this.router.navigate(NAVIGATION.admin.paymentEventDetails(eventId));
  }
}
