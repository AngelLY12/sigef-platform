import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { TableComponent } from '../../../../../shared/components/data-display/table/table.component';
import { TableColumn } from '../../../../../core/models/domain/table-column.model';
import { CurrencyMXNPipe } from '../../../../../shared/pipes/currency-mxn.pipe';
import { PaymentsResponse } from '../../../models/payments/payments-response.model';

@Component({
  selector: 'app-payments-table',
  standalone: true,
  imports: [CommonModule, TableComponent],
  templateUrl: './payments-table.component.html',
  styleUrl: './payments-table.component.scss',
  providers: [CurrencyMXNPipe],
})
export class PaymentsTableComponent {
  @Input() data: PaymentsResponse[] = [];

  constructor(private currencyPipe: CurrencyMXNPipe) {}

  get paymentsColumns(): TableColumn<PaymentsResponse>[] {
    return [
      {
        key: 'id',
        label: 'ID',
        type: 'number',
      },
      {
        key: 'concept',
        label: 'Concepto',
      },
      {
        key: 'amount',
        label: 'Monto',
        format: (value) => this.currencyPipe.transform(value),
      },
      {
        key: 'amount_received',
        label: 'Monto recibido',
        format: (value) => this.currencyPipe.transform(value),
      },
      {
        key: 'date',
        label: 'fecha',
        type: 'date',
      },
      {
        key: 'method',
        label: 'Método de pago',
      },
      {
        key: 'fullName',
        label: 'Nombre del estudiante',
      },
    ];
  }
}
