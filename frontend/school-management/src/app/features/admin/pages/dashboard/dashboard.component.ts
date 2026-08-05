import { Component, inject, OnInit } from '@angular/core';
import { AdminService } from '../../../../core/api/admin/admin.api.service';
import { DashboardSummary } from '../../models/response/dashboard-summary.model';
import { CommonModule } from '@angular/common';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { PageLayoutComponent } from '../../../../shared/components/layout/page-layout/page-layout.component';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { ListController } from '../../../../core/utils/list-controller.utils';
import { BASE_DASHBOARD_LIST_PARAMS, DashboardRequest } from '../../models/request/dashboard-request.model';
import { createParams } from '../../../../core/utils/params-helper.utils';
import { DashboardOverviewComponent } from '../../components/dashboard/dashboard-overview/dashboard-overview.component';
import { DashboardChartsComponent } from '../../components/dashboard/dashboard-charts/dashboard-charts.component';
import { DashboardDetailsComponent } from '../../components/dashboard/dashboard-details/dashboard-details.component';
import { DashboardCategoryDetailsComponent } from '../../components/dashboard/dashboard-category-details/dashboard-category-details.component';

@Component({
  selector: 'app-dashboard',
  imports: [
    CommonModule,
    DashboardOverviewComponent,
    DashboardChartsComponent,
    DashboardDetailsComponent,
    DashboardCategoryDetailsComponent,
    PageLayoutComponent,
  ],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss',
})
export class DashboardComponent implements OnInit {
  private dashboardService = inject(AdminService);
  private listController!: ListController<DashboardRequest>;

  summary?: DashboardSummary;
  state: LoadingState = 'loading';
  dashboardListParams: DashboardRequest = createParams(BASE_DASHBOARD_LIST_PARAMS);

  get hasAlerts(): boolean {
    if (!this.summary) return false;
    return (
      this.summary.systemAlerts.users_without_role > 0 ||
      this.summary.systemAlerts.students_without_n_control > 0 ||
      this.summary.systemAlerts.students_without_student_details > 0
    );
  }

  get getTotalAlerts(): number {
    if (!this.summary) return 0;
    return (
      this.summary.systemAlerts.users_without_role +
      this.summary.systemAlerts.students_without_n_control +
      this.summary.systemAlerts.students_without_student_details
    );
  }

  ngOnInit() {
    this.listController = new ListController<DashboardRequest>(
          () => this.dashboardListParams,
          (params) => (this.dashboardListParams = params),
          () => this.loadSummary(),
        );
    this.loadSummary();
  }

  loadSummary() {
    this.state = 'loading';
    this.dashboardService.getSummary(this.dashboardListParams).subscribe({
      next: (response) => {
        this.summary = response.data.summary;
        this.state = 'success';
      },
      error: () => {
        this.state = 'error';
      },
    });
  }

  onRefreshData() {
    const updatedParams = QueryParamsHelper.refreshData(this.dashboardListParams);
    this.listController.update(updatedParams);
  }
}
