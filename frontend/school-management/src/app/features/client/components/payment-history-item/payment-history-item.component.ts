import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { PaymentHistoryResponse } from '../../models/payment-history/payment-history-response.model';
import { CurrencyMXNPipe } from '../../../../shared/pipes/currency-mxn.pipe';

@Component({
  selector: 'app-payment-history-item',
  imports: [CommonModule,CurrencyMXNPipe],
  templateUrl: './payment-history-item.component.html',
  styleUrl: './payment-history-item.component.scss',
})
export class PaymentHistoryItemComponent {
  @Input({ required: true })
  payment!: PaymentHistoryResponse;

  get statusClass(): string {
    const map: Record<string, string> = {
      Pagado: 'status-paid',
      Completado: 'status-paid',
      Pendiente: 'status-pending',
      'No pagado': 'status-pending',
      'Requiere acción': 'status-action',
      'Pago parcial': 'status-action',
      Sobrepago: 'status-action',
      Fallido: 'status-failed',
    };

    return map[this.payment.status] ?? 'status-pending';
  }
}
