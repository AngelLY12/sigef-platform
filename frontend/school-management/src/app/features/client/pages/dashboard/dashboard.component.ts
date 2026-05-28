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

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    PageLayoutComponent,
    KpiCardComponent,
    TableComponent,
    RecordListComponent,
    SectionDividerComponent,
    ChartCardComponent,
    CurrencyMXNPipe
  ],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss',
})
export class DashboardComponent implements OnInit {
  private dashboardService = inject(DashboardService);
  private chartService = inject(ChartService);
  paymentsChartData!: ChartData<'bar'>;
  barChartOptions!: ChartConfiguration<'bar'>['options'];

  distributionChartData!: ChartData<'doughnut'>;
  pieChartOptions!: ChartConfiguration<'doughnut'>['options'];
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
        this.initCharts();
        this.state = 'success';
      },
      error: () => {
        this.state = 'error';
      },
    });
  }
  private initCharts() {
    if (!this.paidSummary || !this.pendingSummary || !this.overdueSummary)
      return;
    const entries = Object.entries(this.paidSummary.paymentsByMonth || {}).sort(
      ([a], [b]) => a.localeCompare(b),
    );

    this.paymentsChartData = this.chartService.buildBarChart({
      labels: entries.map(([key]) => key),
      data: entries.map(([, value]) => Number(value)),
      label: 'Pagos',
    });
    this.barChartOptions = this.chartService.buildBarOptions();

    this.distributionChartData = this.chartService.buildDoughnutChart({
      labels: ['Pagado', 'Pendiente', 'Vencido'],
      data: [
        Number(this.paidSummary.totalPayments || 0),
        Number(this.pendingSummary.totalAmount || 0),
        Number(this.overdueSummary.totalAmount || 0),
      ],
    });

    this.pieChartOptions = this.chartService.buildDoughnutOptions();
  }
  historyColumns = [
    { key: 'concept', label: 'Concepto' },
    { key: 'amount', label: 'Monto' },
    { key: 'amount_received', label: 'Recibido' },
    {
      key: 'status',
      label: 'Estado',
      badgeType: (value: string) => {
        switch (value) {
          case 'paid':
            return 'success';
          case 'pending':
            return 'warning';
          case 'overdue':
            return 'danger';
          default:
            return 'default';
        }
      },
    },
    { key: 'date', label: 'Fecha' },
  ];
}
