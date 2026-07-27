import { Component, inject, Input, OnChanges } from '@angular/core';
import { ChartService } from '../../../../../core/services/chart.service';
import { ChartConfiguration, ChartData } from 'chart.js';
import { PaidData } from '../../../models/dashboard/paid-concepts-response.model';
import { TotalPending } from '../../../models/dashboard/pending-concepts-response.model';
import { ChartCardComponent } from '../../../../../shared/components/data-display/chart-card/chart-card.component';
import { SectionDividerComponent } from '../../../../../shared/components/layout/section-divider/section-divider.component';
import { ChartFooterStatsComponent } from '../../../../../shared/components/data-display/chart-footer-stats/chart-footer-stats.component';
import { ChartStatItemConfig } from '../../../../../core/models/domain/charts/chart-stat-item-config.model';

@Component({
  selector: 'app-dashboard-charts',
  standalone: true,
  imports: [
    ChartCardComponent,
    SectionDividerComponent,
    ChartFooterStatsComponent,
  ],
  templateUrl: './dashboard-charts.component.html',
  styleUrl: './dashboard-charts.component.scss',
})
export class DashboardChartsComponent implements OnChanges {
  @Input() pendingSummary: TotalPending | null = null;
  @Input() paidSummary: PaidData | null = null;
  @Input() overdueSummary: TotalPending | null = null;

  private chartService = inject(ChartService);
  paymentsLineData!: ChartData<'line'>;
  lineChartOptions!: ChartConfiguration<'line'>['options'];

  distributionChartData!: ChartData<'doughnut'>;
  doghnutChartOptions!: ChartConfiguration<'doughnut'>['options'];

  ngOnChanges(): void {
    this.initCharts();
  }

  get paymentsStats(): ChartStatItemConfig[] {
    return [
      {
        label: 'Pagado',
        value: this.paidSummary?.totalPayments ?? 0,
      },
      {
        label: 'Pendiente',
        value: this.pendingSummary?.totalAmount ?? 0,
      },
      {
        label: 'Vencido',
        value: this.overdueSummary?.totalAmount ?? 0,
      },

    ];
  }

  private initCharts() {
    if (!this.paidSummary || !this.pendingSummary || !this.overdueSummary)
      return;
    const entries = Object.entries(this.paidSummary.paymentsByMonth || {}).sort(
      ([a], [b]) => a.localeCompare(b),
    );

    this.paymentsLineData = this.chartService.buildLineChart({
      labels: entries.map(([key]) => key),
      datasets: [
        {
          label: 'Pagos',
          data: entries.map(([, value]) => Number(value)),
        },
      ],
    });
    this.lineChartOptions = this.chartService.buildLineOptions();

    this.distributionChartData = this.chartService.buildDoughnutChart({
      labels: ['Pagado', 'Pendiente', 'Vencido'],
      datasets: [
        {
          label: 'Distribución',
          data: [
            Number(this.paidSummary.totalPayments || 0),
            Number(this.pendingSummary.totalAmount || 0),
            Number(this.overdueSummary.totalAmount || 0),
          ],
        },
      ],
    });

    this.doghnutChartOptions = this.chartService.buildDoughnutOptions();
  }
}
