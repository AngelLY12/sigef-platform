import { Component, inject, Input } from '@angular/core';
import { PaymentDetailsResponse } from '../../../models/payment-history/payment-details-response.model';
import { InfoCardItemComponent } from '../../../../../shared/components/data-display/info-card-item/info-card-item.component';
import { InfoCardItemConfig } from '../../../../../core/models/domain/cards/info-card-item-config.model';
import { CurrencyMXNPipe } from '../../../../../shared/pipes/currency-mxn.pipe';

@Component({
  selector: 'app-payment-summary',
  standalone: true,
  imports: [InfoCardItemComponent],
  providers: [CurrencyMXNPipe],
  templateUrl: './payment-summary.component.html',
  styleUrl: './payment-summary.component.scss',
})
export class PaymentSummaryComponent {
  @Input({ required: true })
  payment!: PaymentDetailsResponse;
  private currencyMXNPipe = inject(CurrencyMXNPipe);

  get items(): InfoCardItemConfig[] {
    return [
      {
        icon: 'description',
        label: 'Concepto',
        value: this.payment.concept_name,
      },
      {
        icon: 'verified',
        label: 'Estado',
        value: this.payment.status,
      },
      {
        icon: 'event',
        label: 'Fecha',
        value: this.payment.created_at_human,
      },
      {
        icon: 'payments',
        label: 'Monto',
        value: this.currencyMXNPipe.transform(this.payment.amount),
      },
      {
        icon: 'account_balance_wallet',
        label: 'Monto recibido',
        value: this.currencyMXNPipe.transform(this.payment.amount_received),
      },
      {
        icon: 'balance',
        label: 'Balance',
        value: this.payment.balance,
      },
    ];
  }
}
