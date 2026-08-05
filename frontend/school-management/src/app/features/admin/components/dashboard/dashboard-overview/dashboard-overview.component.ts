import { Component, Input } from '@angular/core';
import { SectionDividerComponent } from '../../../../../shared/components/layout/section-divider/section-divider.component';
import { KpiCardComponent } from '../../../../../shared/components/data-display/cards/kpi-card/kpi-card.component';
import { DashboardSummary } from '../../../models/response/dashboard-summary.model';
import { getPercentage } from '../../../../../core/helpers';

@Component({
  selector: 'app-dashboard-overview',
  imports: [
    SectionDividerComponent,
    KpiCardComponent,
  ],
  templateUrl: './dashboard-overview.component.html',
  styleUrl: './dashboard-overview.component.scss'
})
export class DashboardOverviewComponent {
  @Input({required: true}) summary!: DashboardSummary;
  @Input() totalAlerts: number = 0;
  @Input() hasAlerts: boolean = false;

  get usersPercentage(): number {
    return getPercentage(this.summary.populationSummary.active_users, this.summary.populationSummary.total_users);
  }

}
