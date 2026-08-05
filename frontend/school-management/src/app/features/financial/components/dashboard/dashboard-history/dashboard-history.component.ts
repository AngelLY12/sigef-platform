import { Component, Input } from '@angular/core';
import { SectionDividerComponent } from '../../../../../shared/components/layout/section-divider/section-divider.component';
import { TableComponent } from '../../../../../shared/components/data-display/tables/table/table.component';
import { Paginated } from '../../../../../core/utils/paginated-helper.utils';
import { ConceptsHistoryItems } from '../../../models/dashboard/concepts-history.response.model';

@Component({
  selector: 'app-dashboard-history',
  standalone: true,
  imports: [SectionDividerComponent, TableComponent],
  templateUrl: './dashboard-history.component.html',
  styleUrl: './dashboard-history.component.scss'
})
export class DashboardHistoryComponent {
  @Input({ required: true }) conceptsHistory: Paginated<ConceptsHistoryItems> | null = null;

  historyColumns = [
    { key: 'concept_name', label: 'Concepto' },
    { key: 'amount', label: 'Monto' },
    {
      key: 'status',
      label: 'Estado',
      badgeType: (value: string) => {
        switch (value) {
          case 'paid':
            return 'success';
          case 'pending':
            return 'warning';
          case 'overdue':
            return 'danger';
          default:
            return 'default';
        }
      },
    },
    { key: 'expiration_human', label: 'Vencimiento' },
  ];

}
