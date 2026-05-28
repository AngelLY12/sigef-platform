import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { DashboardService } from '../../../../core/api/financial-staff/dashboard.api.service';
import { ChartService } from '../../../../core/services/chart.service';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { KpiCardComponent } from '../../../../shared/components/data-display/kpi-card/kpi-card.component';
import { SectionDividerComponent } from '../../../../shared/components/layout/section-divider/section-divider.component';
import { ChartCardComponent } from '../../../../shared/components/data-display/chart-card/chart-card.component';
import { TableComponent } from '../../../../shared/components/data-display/table/table.component';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { TotalPending } from '../../models/dashboard/pendig-concept.response.model';
import { TotalStudents } from '../../models/dashboard/students-summary.response.model';
import { PaymentsData } from '../../models/dashboard/payments.response.model';
import { ConceptsHistoryItems } from '../../models/dashboard/concepts-history.response.model';
import { Paginated } from '../../../../core/utils/paginated-helper.utils';
import { ChartConfiguration, ChartData } from 'chart.js';
import { catchError, forkJoin, of } from 'rxjs';
import { CurrencyMXNPipe } from '../../../../shared/pipes/currency-mxn.pipe';
import { ExpandableSectionComponent } from '../../../../shared/components/layout/expandable-section/expandable-section.component';
import { FormsModule } from '@angular/forms';
import { KpiCardConfig } from '../../../../core/models/domain/kpi-card-config.model';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    PageLayoutComponent,
    ExpandableSectionComponent,
    KpiCardComponent,
    SectionDividerComponent,
    ChartCardComponent,
    TableComponent,
    CurrencyMXNPipe,
    FormsModule,
  ],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss',
})
export class DashboardComponent implements OnInit {
  private dashboardService = inject(DashboardService);
  private chartService = inject(ChartService);

  state: LoadingState = 'loading';

  pendingSummary: TotalPending | null = null;
  studentsSummary: TotalStudents | null = null;
  paymentsSummary: PaymentsData | null = null;
  conceptsHistory: Paginated<ConceptsHistoryItems> | null = null;

  paymentsChartData!: ChartData<'bar'>;
  feesChartData!: ChartData<'line'>;
  financialSummaryChartData!: ChartData<'bar'>;
  distributionChartData!: ChartData<'doughnut'>;
  availableBySourceChartData!: ChartData<'bar'>;
  pendingBySourceChartData!: ChartData<'bar'>;

  barOptions!: ChartConfiguration<'bar'>['options'];
  horizontalBarOptions!: ChartConfiguration<'bar'>['options'];
  stackedBarOptions!: ChartConfiguration<'bar'>['options'];
  doughnutOptions!: ChartConfiguration<'doughnut'>['options'];
  lineOptions!: ChartConfiguration<'line'>['options'];

  ngOnInit(): void {
    this.loadDashboard();
  }

  loadDashboard() {
    this.state = 'loading';

    forkJoin({
      pending: this.dashboardService
        .getPending()
        .pipe(catchError(() => of(null))),
      students: this.dashboardService
        .getStudents()
        .pipe(catchError(() => of(null))),
      payments: this.dashboardService
        .getPayments()
        .pipe(catchError(() => of(null))),
      history: this.dashboardService
        .getConceptsHistory()
        .pipe(catchError(() => of(null))),
    }).subscribe({
      next: ({ pending, students, payments, history }) => {
        if (pending) this.pendingSummary = pending;
        if (students) this.studentsSummary = students;
        if (payments) this.paymentsSummary = payments;
        if (history) this.conceptsHistory = history;

        this.initCharts();
        this.state = 'success';
      },
      error: () => (this.state = 'error'),
    });
  }
  refresh() {
    this.loadDashboard();
  }
  createPayout() {
    this.dashboardService.createPayout().subscribe(() => {
      this.refresh();
    });
  }

  private initCharts() {
    if (!this.paymentsSummary) return;

    const paymentsSemesters = Object.keys(
      this.paymentsSummary.paymentsBySemester,
    );
    const payoutsSemesters = Object.keys(
      this.paymentsSummary.payoutsBySemester,
    );
    const feesSemesters = Object.keys(this.paymentsSummary.feesBySemester);

    const payoutTotals = Object.values(
      this.paymentsSummary.payoutsBySemester,
    ).map((s) => Number(s.total));
    const paymentsTotals = Object.values(
      this.paymentsSummary.paymentsBySemester,
    ).map((s) => Number(s.total));
    const feesTotals = Object.values(this.paymentsSummary.feesBySemester).map(
      (s) => Number(s.total),
    );

    const availableSourceData =
      this.paymentsSummary.totalBalanceAvailableBySource;
    const pendingSourceData = this.paymentsSummary.totalBalancePendingBySource;

    this.paymentsChartData = this.chartService.buildBarChart({
      labels: paymentsSemesters,
      data: paymentsTotals,
      label: 'Pagos',
    });

    this.feesChartData = this.chartService.buildLineChart({
      labels: feesSemesters,
      data: feesTotals,
      label: 'Comisiones',
    });

    this.financialSummaryChartData = this.chartService.buildStackedBarChart({
      labels: payoutsSemesters,
      datasets: [
        {
          label: 'Pagos',
          data: payoutTotals,
        },

        {
          label: 'Comisiones',
          data: feesTotals,
        },
      ],
    });

    this.distributionChartData = this.chartService.buildDoughnutChart({
      labels: ['Disponible', 'Pendiente'],
      data: [
        Number(this.paymentsSummary.totalBalanceAvailable),
        Number(this.paymentsSummary.totalBalancePending),
      ],
    });

    this.availableBySourceChartData = this.chartService.buildHorizontalBarChart(
      {
        labels: Object.keys(availableSourceData),
        data: Object.values(availableSourceData).map((v) => Number(v)),
      },
    );

    this.pendingBySourceChartData = this.chartService.buildHorizontalBarChart({
      labels: Object.keys(pendingSourceData),
      data: Object.values(pendingSourceData).map((v) => Number(v)),
    });

    this.lineOptions = this.chartService.buildLineOptions();
    this.barOptions = this.chartService.buildBarOptions();
    this.doughnutOptions = this.chartService.buildDoughnutOptions();
    this.horizontalBarOptions = this.chartService.buildHorizontalBarOptions();
    this.stackedBarOptions = this.chartService.buildStackedBarOptions();
  }

  historyColumns = [
    { key: 'concept_name', label: 'Concepto' },
    { key: 'amount', label: 'Monto' },
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
    { key: 'expiration_human', label: 'Vencimiento' },
  ];

  get kpiPayments(): KpiCardConfig[] {
    if (!this.paymentsSummary) return [];
    return [
      {
        icon: 'attach_money',
        iconType: 'growth',
        label: 'Balance disponible',
        value: this.paymentsSummary?.totalBalanceAvailable,
        subtext: `Ratio: ${this.paymentsSummary?.availablePercentage}%`,
      },
      {
        icon: 'money_off',
        iconType: 'alerts',
        label: 'Balance pendiente',
        value: this.paymentsSummary?.totalBalancePending,
        subtext: `Ratio: ${this.paymentsSummary?.pendingPercentage}%`,
      },
      {
        icon: 'receipt_long',
        iconType: 'inactive',
        label: 'Total de comisiones',
        value: this.paymentsSummary?.totalFees,
        subtext: `Ratio: ${this.paymentsSummary?.feePercentage}%`,
      },
      {
        icon: 'account_balance_wallet',
        iconType: 'inactive',
        label: 'Total neto recibido',
        value: this.paymentsSummary?.totalNetReceived,
        subtext: `Ratio: ${this.paymentsSummary?.netReceivedPercentage}%`,
      },

      {
        icon: 'account_balance_wallet',
        iconType: 'growth',
        label: 'Total neto (desp. comisiones)',
        value: this.paymentsSummary?.totalNetAfterFees,
        subtext: `Ratio: ${this.paymentsSummary?.netAfterFeesPercentage}%`,
      },
      {
        icon: 'account_balance_wallet',
        iconType: 'growth',
        label: 'Ratio total disponible',
        value: this.paymentsSummary?.totalPayouts,
      },
    ];
  }
}
