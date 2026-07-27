import { Component, Input } from '@angular/core';
import { KpiCardComponent } from '../../../../../shared/components/data-display/kpi-card/kpi-card.component';
import { DashboardSummary } from '../../../models/response/dashboard-summary.model';
import { SectionDividerComponent } from '../../../../../shared/components/layout/section-divider/section-divider.component';
import { CommonModule } from '@angular/common';
import { KpiBreakdownComponent } from '../../../../../shared/components/data-display/kpi-breakdown/kpi-breakdown.component';
import { MiniStatsComponent } from '../../../../../shared/components/data-display/mini-stats/mini-stats.component';
import { KpiBreakdownItem } from '../../../../../core/models/domain/cards/kpi-breakdown-item.model';
import { MiniStatItem } from '../../../../../core/models/domain/cards/mini-stat-item.model';

@Component({
  selector: 'app-dashboard-category-details',
  imports: [
    CommonModule,
    KpiCardComponent,
    SectionDividerComponent,
    KpiBreakdownComponent,
    MiniStatsComponent,
  ],
  templateUrl: './dashboard-category-details.component.html',
  styleUrl: './dashboard-category-details.component.scss',
})
export class DashboardCategoryDetailsComponent {
  @Input({ required: true }) summary!: DashboardSummary;

  get inactiveBreakdown(): KpiBreakdownItem[] {
    return [
      {
        label: 'Temp',
        value: this.summary.populationSummary.temporal_inactive_users,
        type: 'warning',
      },
    ];
  }

  get newUsersBreakdown(): KpiBreakdownItem[] {
    return [
      {
        label: 'Mes',
        value: this.summary.recentActivity.new_users_this_month,
        type: 'warning',
      },
    ];
  }

  get academicMiniStats(): MiniStatItem[] {
    return [
      {
        label: 'Sin grupo',
        value: this.summary.academicSummary.students_without_group,
        type: 'warning',
      },
      {
        label: 'Sin semestre',
        value: this.summary.academicSummary.students_without_semester,
        type: 'warning',
      },
    ];
  }
}
