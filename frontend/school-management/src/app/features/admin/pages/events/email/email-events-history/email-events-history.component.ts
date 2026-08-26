import { Component, inject, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../../../shared/components/layout/page-layout/page-layout.component';
import { LoadingState } from '../../../../../../core/models/types/loading-state.type';
import { ActivatedRoute, Router } from '@angular/router';
import { AdminEmailEventsApiService } from '../../../../../../core/api/admin/events/admin-email-events.api.service';
import { Paginated } from '../../../../../../core/utils/paginated-helper.utils';
import { EmailEventResponse } from '../../../../models/response/events/email/email-event.response';
import {
  BASE_EMAIL_EVENT_HISTORY_FILTERS,
  EmailEventHistoryFilters,
} from '../../../../models/request/events/email/email-event-history-filters.model';
import { createParams } from '../../../../../../core/utils/params-helper.utils';
import { FolderTab } from '../../../../../../shared/components/navigation/folder-tabs/folder-tabs-config.model';
import { SelectOption } from '../../../../../../shared/components/form/controls/select/select-option.config.model';
import { enumToOptionsWithLabel } from '../../../../../../core/utils/enum-helper.utils';
import {
  EmailEventType,
  EmailEventTypeLabels,
} from '../../../../models/request/events/email/email-event-type.enum';
import { EMAIL_EVENT_TABS, EMAIL_EVENTS_COLUMNS } from '../../../../config/tabs.config';
import {
  EmailEventSourceType,
  EmailEventSourceTypeLabels,
} from '../../../../models/request/events/email/email-event-source-type.enum';
import { ListController } from '../../../../../../core/utils/list-controller.utils';
import { QueryParamsHelper } from '../../../../../../core/utils/query-params-helper.utils';
import { TableComponent } from '../../../../../../shared/components/data-display/tables/table/table.component';
import { FolderTabsComponent } from '../../../../../../shared/components/navigation/folder-tabs/folder-tabs.component';
import { PaginatorComponent } from '../../../../../../shared/components/data-controls/paginator/paginator.component';
import { TableColumn } from '../../../../../../shared/components/data-display/tables/table/table-column.model';
import { FilterBarComponent } from '../../../../../../shared/components/data-controls/filter-bar/filter-bar.component';
import { SelectComponent } from '../../../../../../shared/components/form/controls/select/select.component';
import { FormsModule } from '@angular/forms';
import { ButtonComponent } from '../../../../../../shared/components/ui/button/button.component';
import { InputComponent } from '../../../../../../shared/components/form/controls/input/input.component';
import { NAVIGATION } from '../../../../../../core/navigation/navigation.config';

@Component({
  selector: 'app-email-events-history',
  imports: [
    PageLayoutComponent,
     TableComponent,
     FolderTabsComponent,
     PaginatorComponent,
     FilterBarComponent,
     SelectComponent,
     FormsModule,
     ButtonComponent,
     InputComponent
    ],
  templateUrl: './email-events-history.component.html',
  styleUrl: './email-events-history.component.scss',
})
export class EmailEventsHistoryComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private emailEventApiService = inject(AdminEmailEventsApiService);
  private listController!: ListController<EmailEventHistoryFilters>;
  historyState: LoadingState = 'idle';
  userId: number | null = null;
  paginatedEmails: Paginated<EmailEventResponse> | null = null;
  emailFilters = createParams(BASE_EMAIL_EVENT_HISTORY_FILTERS);
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

  emailColumns: TableColumn[] = EMAIL_EVENTS_COLUMNS;

  ngOnInit(): void {
    this.userId = this.loadUserIdFromRoute();
    if (!this.userId) return;

    this.listController = new ListController<EmailEventHistoryFilters>(
      () => this.emailFilters,
      (params) => (this.emailFilters = params),
      () => this.loadUserEmailHistory(this.userId!),
    );
    this.loadUserEmailHistory(this.userId);
  }

  loadUserIdFromRoute(): number | null {
    const idParam = this.route.snapshot.paramMap.get('userId');
    if (!idParam) {
      this.historyState = 'error';
      return null;
    }
    return +idParam;
  }

  loadUserEmailHistory(userId: number) {
    this.historyState = 'loading';
    this.emailEventApiService.getUserEmailEventsHistory(userId, this.emailFilters)
    .subscribe({
      next: (res) => {
        this.historyState = 'success';
        this.paginatedEmails = res;
      },
      error: (error) =>{
        if (error.status === 422) {
          this.historyState = 'success';
          return;
        }

        this.historyState = 'error';
      }
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
      this.emailFilters = createParams(BASE_EMAIL_EVENT_HISTORY_FILTERS);
      this.loadUserEmailHistory(this.userId!);
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

    onNavigateToDetails(eventId: number) {
        this.router.navigate(NAVIGATION.admin.emailEventDetails(eventId));
      }

}
