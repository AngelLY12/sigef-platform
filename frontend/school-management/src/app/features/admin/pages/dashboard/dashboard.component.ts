import { DemographicItem } from '../../../../shared/components/data-display/demographics-grid/demographics-grid.component';
import { ChartConfiguration, ChartData } from 'chart.js';
import { Component, inject, OnInit } from '@angular/core';
import { AdminService } from '../../../../core/api/admin.api.service';
import { DashboardSummary } from '../../models/dashboard-summary.model';
import { CommonModule } from '@angular/common';
import { getPercentage } from '../../../../core/helpers';
import { KpiCardComponent } from '../../../../shared/components/data-display/kpi-card/kpi-card.component';
import { ChartCardComponent } from '../../../../shared/components/data-display/chart-card/chart-card.component';
import { InfoCardComponent } from '../../../../shared/components/data-display/info-card/info-card.component';
import { DemographicsGridComponent } from '../../../../shared/components/data-display/demographics-grid/demographics-grid.component';
import { ProgressItem, ProgressListComponent } from '../../../../shared/components/data-display/progress-list/progress-list.component';
import { AlertItem, AlertsListComponent } from '../../../../shared/components/feedback/alerts-list/alerts-list.component';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { ChartService } from '../../../../core/services/chart.service';
import { SectionDividerComponent } from '../../../../shared/components/layout/section-divider/section-divider.component';

@Component({
  selector: 'app-dashboard',
  imports: [CommonModule,
     KpiCardComponent, ChartCardComponent, SectionDividerComponent,
      InfoCardComponent, DemographicsGridComponent, ProgressListComponent, AlertsListComponent, PageLayoutComponent ],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss'
})
export class DashboardComponent implements OnInit {
  private dashboardService = inject(AdminService);
  private chartService = inject(ChartService);
  summary?: DashboardSummary;
  state: LoadingState = 'loading';

  userDistributionChartData!: ChartData<'doughnut'>;
  userDistributionChartOptions!: ChartConfiguration<'doughnut'>['options'];

  activityChartData!: ChartData<'line'>;
  activityChartOptions!: ChartConfiguration<'line'>['options'];

  roleChartData!: ChartData<'doughnut'>;
  academicChartData!: ChartData<'doughnut'>;

  get hasAlerts(): boolean {
    if (!this.summary) return false;
    return (
      this.summary.systemAlerts.users_without_role > 0 ||
      this.summary.systemAlerts.students_without_n_control > 0 ||
      this.summary.systemAlerts.students_without_student_details > 0
    );
  }

  get totalInactiveUsers(): number {
    if (!this.summary) return 0;
    return this.summary.populationSummary.inactive_users +
           this.summary.populationSummary.temporal_inactive_users;
  }

  get totalDeleteUsers(): number {
    if(!this.summary) return 0;
    return this.summary.populationSummary.deleted_users;
  }

  getTotalAlerts(): number {
  if (!this.summary) return 0;
  return (
    this.summary.systemAlerts.users_without_role +
    this.summary.systemAlerts.students_without_n_control +
    this.summary.systemAlerts.students_without_student_details
  );
}

  ngOnInit() {
    this.loadSummary();
  }

  loadSummary() {
    this.state = 'loading';
    this.dashboardService.getSummary().subscribe({
      next: (response) => {
        this.summary = response.data.summary;
        this.initCharts();
        this.state = 'success';
      },
      error: () => {
        this.state= 'error';
      }
    });
  }

   private initCharts() {
    if (!this.summary) return;

    this.userDistributionChartData = this.chartService.buildDoughnutChart({
      labels: ['Activos', 'Inactivos', 'Temp. Inactivos', 'Eliminados'],
      data: [
        this.summary.populationSummary.active_users,
        this.summary.populationSummary.inactive_users,
        this.summary.populationSummary.temporal_inactive_users,
        this.summary.populationSummary.deleted_users
      ]
    });

    this.userDistributionChartOptions = this.chartService.buildDoughnutOptions();
    this.roleChartData = this.chartService.buildDoughnutChart({
      labels: ['Admin', 'Aspirante', 'Financiero', 'Padres', 'Estudiantes', 'Supervisor', 'No verificado'],
      data: [
        this.summary.usersByRoleSummary.admin,
        this.summary.usersByRoleSummary.applicant,
        this.summary.usersByRoleSummary['financial-staff'],
        this.summary.usersByRoleSummary.parent,
        this.summary.usersByRoleSummary.student,
        this.summary.usersByRoleSummary.supervisor,
        this.summary.usersByRoleSummary.unverified
      ]
    });

    this.activityChartData = this.chartService.buildLineChart({
      labels: ['Hoy', 'Esta semana', 'Este mes'],
      data: [
        this.summary.recentActivity.new_users_today,
        this.summary.recentActivity.new_users_this_week,
        this.summary.recentActivity.new_users_this_month
      ],
      label: 'Nuevos usuarios'
    });

    this.activityChartOptions = this.chartService.buildLineOptions();

    this.academicChartData = this.chartService.buildDoughnutChart({
      labels: ['Con carrera', 'Sin carrera', 'Sin semestre', 'Sin grupo'],
      data: [
        this.summary.academicSummary.students_with_career,
        this.summary.academicSummary.students_without_career,
        this.summary.academicSummary.students_without_semester,
        this.summary.academicSummary.students_without_group
      ]
    });
  }

  getPercentage(value: number, total: number): number {
    return getPercentage(value, total);
  }


get academicProgressItems(): ProgressItem[] {
  if (!this.summary) return [];
  return [
    {
      label: 'Con carrera',
      value: this.summary.academicSummary.students_with_career,
      percentage: this.getPercentage(this.summary.academicSummary.students_with_career, this.summary.academicSummary.students_total),
      type: 'success'
    },
    {
      label: 'Sin carrera',
      value: this.summary.academicSummary.students_without_career,
      percentage: this.getPercentage(this.summary.academicSummary.students_without_career, this.summary.academicSummary.students_total),
      type: 'warning'
    },
    {
      label: 'Sin semestre',
      value: this.summary.academicSummary.students_without_semester,
      percentage: this.getPercentage(this.summary.academicSummary.students_without_semester, this.summary.academicSummary.students_total),
      type: 'warning'
    }
  ];
}

get alertItems(): AlertItem[] {
  if (!this.summary) return [];
  const alerts = [];

  if (this.summary.usersByRoleSummary.unverified > 0) {
    alerts.push({
      icon: 'verified',
      title: 'Usuarios sin verificar',
      count: this.summary.usersByRoleSummary.unverified,
    });
  }

  if (this.summary.systemAlerts.users_without_role > 0) {
    alerts.push({
      icon: 'error',
      title: 'Usuarios sin rol',
      count: this.summary.systemAlerts.users_without_role,

    });
  }

  if (this.summary.systemAlerts.students_without_n_control > 0) {
    alerts.push({
      icon: 'badge',
      title: 'Sin número de control',
      count: this.summary.systemAlerts.students_without_n_control,
    });
  }

  if (this.summary.systemAlerts.students_without_student_details > 0) {
    alerts.push({
      icon: 'assignment_late',
      title: 'Datos incompletos',
      count: this.summary.systemAlerts.students_without_student_details,
    });
  }

  return alerts;
}

get demographicItems(): DemographicItem[] {
  if (!this.summary) return [];
  return [
    {
      label: 'Inactivos temporales',
      value: this.summary.populationSummary.temporal_inactive_users,
      type: 'warning'
    },
    {
      label: 'Cuentas eliminadas',
      value: this.summary.populationSummary.deleted_users,
      type: 'danger'
    },
    {
      label: 'Tasa de actividad',
      value: this.getPercentage(this.summary.populationSummary.active_users, this.summary.populationSummary.total_users),
      type: 'success',
      suffix: '%'
    },
    {
      label: 'Retención',
      value: this.getPercentage(
        this.summary.populationSummary.active_users + this.summary.populationSummary.inactive_users,
        this.summary.populationSummary.total_users
      ),
      type: 'primary',
      suffix: '%'
    }
  ];
 }


}
