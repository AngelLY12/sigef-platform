import { Component, Input } from '@angular/core';
import { PaymentsByConceptResponse } from '../../../models/payments/payments-by-concept-response.model';
import { Paginated } from '../../../../../core/utils/paginated-helper.utils';
import { CommonModule } from '@angular/common';
import { TableColumn } from '../../../../../shared/components/data-display/tables/table/table-column.model';
import { CurrencyMXNPipe } from '../../../../../shared/pipes/currency-mxn.pipe';
import { TableComponent } from '../../../../../shared/components/data-display/tables/table/table.component';

@Component({
  selector: 'app-payments-by-concept-table',
  standalone: true,
  imports: [CommonModule, TableComponent],
  templateUrl: './payments-by-concept-table.component.html',
  styleUrl: './payments-by-concept-table.component.scss',
  providers: [CurrencyMXNPipe],
})
export class PaymentsByConceptTableComponent {
  constructor(private currencyPipe: CurrencyMXNPipe) {}

  @Input() data: PaymentsByConceptResponse[] = [];
  get paymentsColumns(): TableColumn<PaymentsByConceptResponse>[] {
    return [
      {
        key: 'concept_name',
        label: 'Nombre del concepto',
      },
      {
        key: 'amount_total',
        label: 'Monto total',
        format: (value) => this.currencyPipe.transform(value),
      },
      {
        key: 'amount_received_total',
        label: 'Monto total recibido',
        format: (value) => this.currencyPipe.transform(value),
      },
      {
        key: 'first_payment_date',
        label: 'Fecha del primer pago',
      },
      {
        key: 'last_payment_date',
        label: 'Fecha del último pago',
      },
      {
        key: 'collection_rate',
        label: 'Tasa de cobro',
        format: (value) => `${value}%`,
      },
    ];
  }
}
