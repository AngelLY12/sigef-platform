import { PaymentsData } from './../../../models/dashboard/payments.response.model';
import { Component, inject, Input, OnChanges, SimpleChanges } from '@angular/core';
import { ChartCardComponent } from '../../../../../shared/components/data-display/charts/chart-card/chart-card.component';
import { AnalysisCardComponent } from '../analysis-card/analysis-card.component';
import { SectionDividerComponent } from '../../../../../shared/components/layout/section-divider/section-divider.component';
import { ChartService } from '../../../../../core/services/chart.service';
import { ChartConfiguration, ChartData } from 'chart.js';
import { PaymentKpiGroup } from '../../../models/payments/payments-kpi-group.type';
import { KpiCardConfig } from '../../../../../shared/components/data-display/cards/kpi-card/kpi-card-config.model';

@Component({
  selector: 'app-dashboard-charts',
  standalone: true,
  imports: [ChartCardComponent, AnalysisCardComponent, SectionDividerComponent],
  templateUrl: './dashboard-charts.component.html',
  styleUrl: './dashboard-charts.component.scss',
})
export class DashboardChartsComponent implements OnChanges {

  @Input({ required: true }) paymentsSummary: PaymentsData | null = null;

  private chartService = inject(ChartService);

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

  ngOnChanges(): void {
    this.initCharts();
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
      datasets: [
        {
          label: 'Comisiones',
          data: feesTotals,
        },
      ],
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
      datasets: [
        {
          label: 'Distribución',
          data: [
            Number(this.paymentsSummary.totalBalanceAvailable),
            Number(this.paymentsSummary.totalBalancePending),
          ],
        },
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
  get kpiPayments(): Record<PaymentKpiGroup, KpiCardConfig[]> {
    if (!this.paymentsSummary) {
      return {
        balance: [],
        fees: [],
        net: [],
      };
    }

    return {
      balance: [
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
      ],

      fees: [
        {
          icon: 'receipt_long',
          iconType: 'inactive',
          label: 'Total de comisiones',
          value: this.paymentsSummary?.totalFees,
          subtext: `Ratio: ${this.paymentsSummary?.feePercentage}%`,
        },
      ],
      net: [
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
      ],
    };
  }
}
