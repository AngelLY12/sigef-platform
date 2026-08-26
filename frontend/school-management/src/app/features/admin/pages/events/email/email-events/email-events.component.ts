import { Component, inject, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../../../shared/components/layout/page-layout/page-layout.component';
import { LoadingState } from '../../../../../../core/models/types/loading-state.type';
import { ListController } from '../../../../../../core/utils/list-controller.utils';
import {
  BASE_EMAIL_EVENT_FILTERS,
  EmailEventFilters,
} from '../../../../models/request/events/email/email-event-filters.model';
import { AdminEmailEventsApiService } from '../../../../../../core/api/admin/events/admin-email-events.api.service';
import { Paginated } from '../../../../../../core/utils/paginated-helper.utils';
import { EmailEventResponse } from '../../../../models/response/events/email/email-event.response';
import {
  EMAIL_EVENT_TABS,
  EMAIL_EVENTS_COLUMNS,
} from '../../../../config/tabs.config';
import { FolderTab } from '../../../../../../shared/components/navigation/folder-tabs/folder-tabs-config.model';
import { createParams } from '../../../../../../core/utils/params-helper.utils';
import { QueryParamsHelper } from '../../../../../../core/utils/query-params-helper.utils';
import { FolderTabsComponent } from '../../../../../../shared/components/navigation/folder-tabs/folder-tabs.component';
import { TableComponent } from '../../../../../../shared/components/data-display/tables/table/table.component';
import { FilterBarComponent } from '../../../../../../shared/components/data-controls/filter-bar/filter-bar.component';
import { TableColumn } from '../../../../../../shared/components/data-display/tables/table/table-column.model';
import { PaginatorComponent } from '../../../../../../shared/components/data-controls/paginator/paginator.component';
import { SelectComponent } from '../../../../../../shared/components/form/controls/select/select.component';
import { InputComponent } from '../../../../../../shared/components/form/controls/input/input.component';
import { SelectOption } from '../../../../../../shared/components/form/controls/select/select-option.config.model';
import { enumToOptionsWithLabel } from '../../../../../../core/utils/enum-helper.utils';
import {
  EmailEventType,
  EmailEventTypeLabels,
} from '../../../../models/request/events/email/email-event-type.enum';
import {
  EmailEventSourceType,
  EmailEventSourceTypeLabels,
} from '../../../../models/request/events/email/email-event-source-type.enum';
import { FormsModule } from '@angular/forms';
import { EmailEventFiltersHelper } from '../../../../utils/email-event-filters.helper.utils';
import { ButtonComponent } from '../../../../../../shared/components/ui/button/button.component';
import { Router } from '@angular/router';
import { NAVIGATION } from '../../../../../../core/navigation/navigation.config';

@Component({
  selector: 'app-email-events',
  standalone: true,
  imports: [
    PageLayoutComponent,
    FolderTabsComponent,
    TableComponent,
    FilterBarComponent,
    PaginatorComponent,
    SelectComponent,
    InputComponent,
    FormsModule,
    ButtonComponent,
  ],
  templateUrl: './email-events.component.html',
  styleUrl: './email-events.component.scss',
})
export class EmailEventsComponent implements OnInit {
  private emailEventsApi = inject(AdminEmailEventsApiService);
  private router = inject(Router);
  private listController!: ListController<EmailEventFilters>;
  eventsState: LoadingState = 'idle';
  paginatedEmails: Paginated<EmailEventResponse> | null = null;
  emailFilters = createParams(BASE_EMAIL_EVENT_FILTERS);
  activeEmailTab = '';
  readonly emailTabs: FolderTab[] = EMAIL_EVENT_TABS;
  eventTypeOptions: SelectOption[] = enumToOptionsWithLabel(
    EmailEventType,
    EmailEventTypeLabels,
  );
  eventSourceTypeOptions: SelectOption[] = enumToOptionsWithLabel(
    EmailEventSourceType,
    EmailEventSourceTypeLabels,
  );
  pendingRecipientEmail: string | null = null;
  pendingSourceId: string | null = null;
  userId: number | null = null;

  emailColumns: TableColumn[] = EMAIL_EVENTS_COLUMNS;

  ngOnInit(): void {
    this.listController = new ListController<EmailEventFilters>(
      () => this.emailFilters,
      (params) => (this.emailFilters = params),
      () => this.loadEvents(),
    );

    this.loadEvents();
  }

  loadEvents(): void {
    this.eventsState = 'loading';
    this.emailEventsApi.getEmailEvents(this.emailFilters).subscribe({
      next: (res) => {
        this.paginatedEmails = res;
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

  onPageChange(newPage: number) {
    const updatedParams = QueryParamsHelper.changePage(
      this.emailFilters,
      newPage,
    );
    this.listController.update(updatedParams);
  }

  onStatusTabChange(status: string) {
    this.activeEmailTab = status;
    const updatedParams = QueryParamsHelper.changeStatus(
      this.emailFilters,
      status || null,
    );
    this.listController.update(updatedParams);
  }

  onPageSizeChange(newSize: number) {
    const updatedParams = QueryParamsHelper.changePageSize(
      this.emailFilters,
      newSize,
    );
    this.listController.update(updatedParams);
  }
  onResetFilters() {
    this.emailFilters = createParams(BASE_EMAIL_EVENT_FILTERS);
    this.pendingRecipientEmail = null;
    this.pendingSourceId = null;
    this.userId = null;
    this.loadEvents();
  }

  onRefreshData() {
    const updatedParams = QueryParamsHelper.refreshData(this.emailFilters);
    this.listController.update(updatedParams);
  }

  onEventTypeChange(eventType: string) {
    const updatedParams = QueryParamsHelper.changeEventType(
      this.emailFilters,
      eventType,
    );
    this.listController.update(updatedParams);
  }

  onEventSourceChange(sourceType: string) {
    const updatedParams = QueryParamsHelper.changeSourceType(
      this.emailFilters,
      sourceType,
    );
    this.listController.update(updatedParams);
  }

  onFromChange(from: string) {
    const updatedParams = QueryParamsHelper.changeFromRange(
      this.emailFilters,
      from,
    );
    this.listController.update(updatedParams);
  }

  onToChange(to: string) {
    const updatedParams = QueryParamsHelper.changeToRange(
      this.emailFilters,
      to,
    );
    this.listController.update(updatedParams);
  }

  onSearchById(): void {
    const sourceId = this.pendingSourceId?.trim() || null;
    if (sourceId === null) {
      return;
    }

    const updatedParams = EmailEventFiltersHelper.changeSourceId(
      this.emailFilters,
      sourceId,
    );

    if (updatedParams.sourceId === this.emailFilters.sourceId) {
      return;
    }

    this.listController.update(updatedParams);
  }

  onSearchByRecipient(): void {
    const recipientEmail = this.pendingRecipientEmail?.trim() || null;

    if (recipientEmail === null) {
      return;
    }
    let updatedParams = EmailEventFiltersHelper.changeRecipientEmail(
      this.emailFilters,
      recipientEmail,
    );

    if (updatedParams.recipientEmail === this.emailFilters.recipientEmail) {
      return;
    }

    this.listController.update(updatedParams);
  }

  onSearchByUserId(): void {
    const userId = this.userId || null;

    if (userId === null) {
      return;
    }
    let updatedParams = EmailEventFiltersHelper.changeUser(
      this.emailFilters,
      userId,
    );

    if (updatedParams.userId === this.emailFilters.userId) {
      return;
    }

    this.listController.update(updatedParams);
  }

  onNavigateToHistory(userId: number) {
    this.router.navigate(NAVIGATION.admin.emailEventsHistory(userId));
  }
  onNavigateToDetails(eventId: number) {
    this.router.navigate(NAVIGATION.admin.emailEventDetails(eventId));
  }
}
