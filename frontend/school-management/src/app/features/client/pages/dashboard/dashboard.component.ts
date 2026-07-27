import { Paginated } from './../../../../core/utils/paginated-helper.utils';
import { TotalPending } from './../../models/dashboard/pending-concepts-response.model';
import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { KpiCardComponent } from '../../../../shared/components/data-display/kpi-card/kpi-card.component';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { DashboardService } from '../../../../core/api/students/dashboard.api.service';
import { PaidData } from '../../models/dashboard/paid-concepts-response.model';
import { PaymentHistoryItem } from '../../models/dashboard/payment-history-response.model';
import { forkJoin } from 'rxjs';
import { TableComponent } from '../../../../shared/components/data-display/table/table.component';
import { RecordListComponent } from '../../../../shared/components/data-display/record-list/record-list.component';
import { SectionDividerComponent } from '../../../../shared/components/layout/section-divider/section-divider.component';
import { ChartCardComponent } from '../../../../shared/components/data-display/chart-card/chart-card.component';
import { ChartService } from '../../../../core/services/chart.service';
import { ChartConfiguration, ChartData } from 'chart.js';
import { CurrencyMXNPipe } from '../../../../shared/pipes/currency-mxn.pipe';
import { getPercentage } from '../../../../core/helpers';
import { DashboardKpisComponent } from '../../components/dashboard/dashboard-kpis/dashboard-kpis.component';
import { DASHBOARD_HISTORY_COLUMS } from '../../config/client.config';
import { DashboardChartsComponent } from '../../components/dashboard/dashboard-charts/dashboard-charts.component';
import { DashboardHistoryComponent } from '../../components/dashboard/dashboard-history/dashboard-history.component';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    PageLayoutComponent,
    DashboardKpisComponent,
    DashboardChartsComponent,
    DashboardHistoryComponent
  ],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss',
})
export class DashboardComponent implements OnInit {
  private dashboardService = inject(DashboardService);
  pendingSummary: TotalPending | null = null;
  paidSummary: PaidData | null = null;
  overdueSummary: TotalPending | null = null;
  paymentHistory: Paginated<PaymentHistoryItem> | null = null;
  state: LoadingState = 'loading';

  ngOnInit(): void {
    this.loadDashboard();
  }

  loadDashboard() {
    this.state = 'loading';
    forkJoin({
      pending: this.dashboardService.getPending(),
      paid: this.dashboardService.getPaid(),
      overdue: this.dashboardService.getOverdue(),
      history: this.dashboardService.getHistory(),
    }).subscribe({
      next: ({ pending, paid, overdue, history }) => {
        this.pendingSummary = pending;
        this.paidSummary = paid;
        this.overdueSummary = overdue;
        this.paymentHistory = history;
        this.state = 'success';
      },
      error: () => {
        this.state = 'error';
      },
    });
  }
}
