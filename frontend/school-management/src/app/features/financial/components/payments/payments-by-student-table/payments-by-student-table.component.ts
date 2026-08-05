import { Component, Input } from '@angular/core';
import { Paginated } from '../../../../../core/utils/paginated-helper.utils';
import { CommonModule } from '@angular/common';
import { PaymentsByStudentResponse } from '../../../models/payments/payments-by-student-response.model';
import { TableComponent } from '../../../../../shared/components/data-display/tables/table/table.component';
import { TableColumn } from '../../../../../shared/components/data-display/tables/table/table-column.model';
import { CurrencyMXNPipe } from '../../../../../shared/pipes/currency-mxn.pipe';

@Component({
  selector: 'app-payments-by-student-table',
  standalone: true,
  imports: [CommonModule, TableComponent],
  templateUrl: './payments-by-student-table.component.html',
  styleUrl: './payments-by-student-table.component.scss',
  providers: [CurrencyMXNPipe],
})
export class PaymentsByStudentTableComponent {
  @Input() data: PaymentsByStudentResponse[] = [];
  constructor(private currencyPipe: CurrencyMXNPipe) {}

  get paymentsColumns(): TableColumn<PaymentsByStudentResponse>[] {
    return [
      {
        key: 'userId',
        label: 'ID',
        type: 'number',
      },
      {
        key: 'fullName',
        label: 'Nombre del estudiante',
      },
      {
        key: 'n_control',
        label: 'Número de control',
      },
      {
        key: 'semestre',
        label: 'Semestre',
        format: (value) => `${value}°`,
      },
      {
        key: 'career_name',
        label: 'Carrera',
      },
      {
        key: 'num_pending',
        label: 'Cantidad de pendientes',
        type: 'number',
        badgeType: 'warning'
      },
      {
        key: 'total_amount_pending',
        label: 'Monto total pendiente',
        format: (value) => this.currencyPipe.transform(value),
      },
      {
        key: 'num_expired',
        label: 'Cantidad de vencidos',
        type: 'number',
        badgeType: 'error'
      },
      {
        key: 'expired_amount',
        label: 'Monto total vencido',
        format: (value) => this.currencyPipe.transform(value),
      },
      {
        key: 'num_paid',
        label: 'Cantidad de pagados',
        type: 'number',
        badgeType: 'success'
      },
      {
        key: 'total_paid',
        label: 'Monto total pagado',
        format: (value) => this.currencyPipe.transform(value),
      },
    ];
  }
}
