import { Component, Input } from '@angular/core';
import { PaidData } from '../../../models/dashboard/paid-concepts-response.model';
import { TotalPending } from '../../../models/dashboard/pending-concepts-response.model';
import { KpiCardComponent } from '../../../../../shared/components/data-display/kpi-card/kpi-card.component';
import { CurrencyMXNPipe } from '../../../../../shared/pipes/currency-mxn.pipe';
import { getPercentage } from '../../../../../core/helpers';

@Component({
  selector: 'app-dashboard-kpis',
  standalone: true,
  imports: [KpiCardComponent, CurrencyMXNPipe],
  templateUrl: './dashboard-kpis.component.html',
  styleUrl: './dashboard-kpis.component.scss',
})
export class DashboardKpisComponent {
  @Input() pendingSummary: TotalPending | null = null;
  @Input() paidSummary: PaidData | null = null;
  @Input() overdueSummary: TotalPending | null = null;

  get recoveryRate(): string {
    const paid = Number(this.paidSummary?.totalPayments ?? 0);

    const total =
      paid +
      Number(this.pendingSummary?.totalAmount ?? 0) +
      Number(this.overdueSummary?.totalAmount ?? 0);

    const percentage = getPercentage(paid, total);

    return `${percentage}%`;
  }
}
