import { Component, Input } from '@angular/core';
import { KpiCardComponent } from '../../../../../shared/components/data-display/kpi-card/kpi-card.component';
import { CurrencyMXNPipe } from '../../../../../shared/pipes/currency-mxn.pipe';
import { TotalPending } from '../../../models/dashboard/pendig-concept.response.model';
import { TotalStudents } from '../../../models/dashboard/students-summary.response.model';
import { PaymentsData } from '../../../models/dashboard/payments.response.model';

@Component({
  selector: 'app-dashboard-kpis',
  standalone: true,
  imports: [KpiCardComponent, CurrencyMXNPipe],
  templateUrl: './dashboard-kpis.component.html',
  styleUrl: './dashboard-kpis.component.scss',
})
export class DashboardKpisComponent {
  @Input({required: true }) pendingSummary: TotalPending | null = null;
  @Input({required: true}) studentsSummary: TotalStudents | null = null;
  @Input({required: true}) paymentsSummary: PaymentsData | null = null;
}
