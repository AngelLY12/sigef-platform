import { Component, Input } from '@angular/core';
import { AlertItem, AlertsListComponent } from '../../../../../shared/components/feedback/alerts-list/alerts-list.component';
import { ProgressItem, ProgressListComponent } from '../../../../../shared/components/data-display/progress-list/progress-list.component';
import { DemographicItem, DemographicsGridComponent } from '../../../../../shared/components/data-display/demographics-grid/demographics-grid.component';
import { InfoCardComponent } from '../../../../../shared/components/data-display/info-card/info-card.component';
import { SectionDividerComponent } from '../../../../../shared/components/layout/section-divider/section-divider.component';
import { DashboardSummary } from '../../../models/response/dashboard-summary.model';
import { getPercentage } from '../../../../../core/helpers';

@Component({
  selector: 'app-dashboard-details',
  imports: [
    SectionDividerComponent,
    InfoCardComponent,
    DemographicsGridComponent,
    ProgressListComponent,
    AlertsListComponent,
  ],
  templateUrl: './dashboard-details.component.html',
  styleUrl: './dashboard-details.component.scss',
})
export class DashboardDetailsComponent {
  @Input({ required: true }) summary!: DashboardSummary;

  get academicProgressItems(): ProgressItem[] {
    if (!this.summary) return [];
    return [
      {
        label: 'Con carrera',
        value: this.summary.academicSummary.students_with_career,
        percentage: getPercentage(
          this.summary.academicSummary.students_with_career,
          this.summary.academicSummary.students_total,
        ),
        type: 'success',
      },
      {
        label: 'Sin carrera',
        value: this.summary.academicSummary.students_without_career,
        percentage: getPercentage(
          this.summary.academicSummary.students_without_career,
          this.summary.academicSummary.students_total,
        ),
        type: 'warning',
      },
      {
        label: 'Sin semestre',
        value: this.summary.academicSummary.students_without_semester,
        percentage: getPercentage(
          this.summary.academicSummary.students_without_semester,
          this.summary.academicSummary.students_total,
        ),
        type: 'warning',
      },
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
        type: 'warning',
      },
      {
        label: 'Cuentas eliminadas',
        value: this.summary.populationSummary.deleted_users,
        type: 'danger',
      },
      {
        label: 'Tasa de actividad',
        value: getPercentage(
          this.summary.populationSummary.active_users,
          this.summary.populationSummary.total_users,
        ),
        type: 'success',
        suffix: '%',
      },
      {
        label: 'Retención',
        value: getPercentage(
          this.summary.populationSummary.active_users +
            this.summary.populationSummary.inactive_users,
          this.summary.populationSummary.total_users,
        ),
        type: 'primary',
        suffix: '%',
      },
    ];
  }
}
