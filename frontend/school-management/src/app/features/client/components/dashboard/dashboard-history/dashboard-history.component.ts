import { Component, Input } from '@angular/core';
import { SectionDividerComponent } from '../../../../../shared/components/layout/section-divider/section-divider.component';
import { TableComponent } from '../../../../../shared/components/data-display/tables/table/table.component';
import { DASHBOARD_HISTORY_COLUMS } from '../../../config/client.config';
import { PaymentHistoryItem } from '../../../models/dashboard/payment-history-response.model';
import { Paginated } from '../../../../../core/utils/paginated-helper.utils';

@Component({
  selector: 'app-dashboard-history',
  standalone: true,
  imports: [SectionDividerComponent, TableComponent],
  templateUrl: './dashboard-history.component.html',
  styleUrl: './dashboard-history.component.scss'
})
export class DashboardHistoryComponent {
  @Input() paymentHistory: Paginated<PaymentHistoryItem> | null = null
  historyColumns = DASHBOARD_HISTORY_COLUMS;

}
