import { Component, inject, Input, OnChanges } from '@angular/core';
import { ChartCardComponent } from '../../../../../shared/components/data-display/chart-card/chart-card.component';
import { SectionDividerComponent } from '../../../../../shared/components/layout/section-divider/section-divider.component';
import { ChartFooterLegendComponent } from '../../../../../shared/components/data-display/chart-footer-legend/chart-footer-legend.component';
import { ChartFooterStatsComponent } from '../../../../../shared/components/data-display/chart-footer-stats/chart-footer-stats.component';
import { DashboardSummary } from '../../../models/response/dashboard-summary.model';
import { ChartLegendItemConfig } from '../../../../../core/models/domain/charts/chart-legend-item-config.model';
import { ChartStatItemConfig } from '../../../../../core/models/domain/charts/chart-stat-item-config.model';
import { ChartService } from '../../../../../core/services/chart.service';
import { ChartConfiguration, ChartData } from 'chart.js';

@Component({
  selector: 'app-dashboard-charts',
  imports: [
    SectionDividerComponent,
    ChartCardComponent,
    ChartFooterStatsComponent,
    ChartFooterLegendComponent,
  ],
  templateUrl: './dashboard-charts.component.html',
  styleUrl: './dashboard-charts.component.scss',
})
export class DashboardChartsComponent implements OnChanges {
  @Input({ required: true }) summary!: DashboardSummary;
  private chartService = inject(ChartService);

  userDistributionChartData!: ChartData<'doughnut'>;
  userDistributionChartOptions!: ChartConfiguration<'doughnut'>['options'];

  activityChartData!: ChartData<'line'>;
  activityChartOptions!: ChartConfiguration<'line'>['options'];

  roleChartData!: ChartData<'doughnut'>;
  academicChartData!: ChartData<'doughnut'>;

  ngOnChanges(): void {
    if (!this.summary) return;

    this.initCharts();
  }

  get userDistributionLegend(): ChartLegendItemConfig[] {
    return [
      {
        label: 'Activos',
        value: this.summary.populationSummary.active_users,
        type: 'active',
      },
      {
        label: 'Inactivos',
        value: this.totalInactiveUsers,
        type: 'inactive',
      },
      {
        label: 'Eliminados',
        value: this.totalDeleteUsers,
        type: 'danger',
      },
    ];
  }

  get roleStats(): ChartStatItemConfig[] {
    return [
      {
        label: 'Admin',
        value: this.summary.usersByRoleSummary.admin,
      },
      {
        label: 'Aspirante',
        value: this.summary.usersByRoleSummary.applicant,
      },
      {
        label: 'Personal financiero',
        value: this.summary.usersByRoleSummary['financial-staff'],
      },
      {
        label: 'Padres',
        value: this.summary.usersByRoleSummary.parent,
      },
      {
        label: 'Estudiantes',
        value: this.summary.usersByRoleSummary.student,
      },
      {
        label: 'Supervisor',
        value: this.summary.usersByRoleSummary.supervisor,
      },
      {
        label: 'Sin verificar',
        value: this.summary.usersByRoleSummary.unverified,
      },
    ];
  }

  get academicStats(): ChartStatItemConfig[] {
    return [
      {
        label: 'Con carrera',
        value: this.summary.academicSummary.students_with_career,
      },
      {
        label: 'Sin carrera',
        value: this.summary.academicSummary.students_without_career,
      },
      {
        label: 'Sin semestre',
        value: this.summary.academicSummary.students_without_semester,
      },
      {
        label: 'Sin grupo',
        value: this.summary.academicSummary.students_without_group,
      },
    ];
  }

  get totalInactiveUsers(): number {
    if (!this.summary) return 0;
    return (
      this.summary.populationSummary.inactive_users +
      this.summary.populationSummary.temporal_inactive_users
    );
  }

  get totalDeleteUsers(): number {
    if (!this.summary) return 0;
    return this.summary.populationSummary.deleted_users;
  }

  private initCharts() {
    if (!this.summary) return;

    this.userDistributionChartData = this.chartService.buildDoughnutChart({
      labels: ['Activos', 'Inactivos', 'Temp. Inactivos', 'Eliminados'],
      datasets: [
        {
          label: 'Distribución',
          data: [
            this.summary.populationSummary.active_users,
            this.summary.populationSummary.inactive_users,
            this.summary.populationSummary.temporal_inactive_users,
            this.summary.populationSummary.deleted_users,
          ],
        },
      ],
    });

    this.userDistributionChartOptions =
      this.chartService.buildDoughnutOptions();
    this.roleChartData = this.chartService.buildDoughnutChart({
      labels: [
        'Admin',
        'Aspirante',
        'Financiero',
        'Padres',
        'Estudiantes',
        'Supervisor',
        'No verificado',
      ],
      datasets: [
        {
          label: 'Distribución',
          data: [
            this.summary.usersByRoleSummary.admin,
            this.summary.usersByRoleSummary.applicant,
            this.summary.usersByRoleSummary['financial-staff'],
            this.summary.usersByRoleSummary.parent,
            this.summary.usersByRoleSummary.student,
            this.summary.usersByRoleSummary.supervisor,
            this.summary.usersByRoleSummary.unverified,
          ],
        },
      ],
    });

    this.activityChartData = this.chartService.buildLineChart({
      labels: ['Hoy', 'Esta semana', 'Este mes'],
      datasets: [
        {
          label: 'Nuevos usuarios',
          data: [
            this.summary.recentActivity.new_users_today,
            this.summary.recentActivity.new_users_this_week,
            this.summary.recentActivity.new_users_this_month,
          ],
        },
      ],
    });

    this.activityChartOptions = this.chartService.buildLineOptions();

    this.academicChartData = this.chartService.buildDoughnutChart({
      labels: ['Con carrera', 'Sin carrera', 'Sin semestre', 'Sin grupo'],
      datasets: [
        {
          label: 'Distribución',
          data: [
            this.summary.academicSummary.students_with_career,
            this.summary.academicSummary.students_without_career,
            this.summary.academicSummary.students_without_semester,
            this.summary.academicSummary.students_without_group,
          ],
        },
      ],
    });
  }
}
