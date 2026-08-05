import { Component, Input } from '@angular/core';
import {
  CardPaymentMethod,
  OxxoPaymentMethod,
  PaymentMethodDetails,
  SpeiPaymentMethod,
  UnknownPaymentMethod,
} from '../../../models/payment-history/payment-method-details-type.model';
import { InfoCardItemComponent } from '../../../../../shared/components/data-display/cards/info-card-item/info-card-item.component';
import { InfoCardActionConfig } from '../../../../../shared/components/data-display/cards/info-card/info-card-config.model';
import { InfoCardItemConfig } from '../../../../../shared/components/data-display/cards/info-card-item/info-card-item-config.model';

@Component({
  selector: 'app-payment-method',
  imports: [InfoCardItemComponent],
  templateUrl: './payment-method.component.html',
  styleUrl: './payment-method.component.scss',
})
export class PaymentMethodComponent {
  @Input({ required: true })
  paymentMethod!: PaymentMethodDetails;

  get items(): InfoCardItemConfig[] {
    switch (this.paymentMethod.type) {
      case 'tarjeta':
        return this.cardItems(this.paymentMethod);

      case 'oxxo':
        return this.oxxoItems(this.paymentMethod);

      case 'spei':
        return this.speiItems(this.paymentMethod);

      default:
        return [
          {
            icon: 'help',
            label: 'Tipo',
            value: this.paymentMethod.original_type ?? 'Desconocido',
          },
        ];
    }
  }

  private cardItems(paymentMethod: CardPaymentMethod): InfoCardItemConfig[] {
    const items: InfoCardItemConfig[] = [
      {
        icon: 'credit_card',
        label: 'Tipo',
        value: 'Tarjeta',
      },
      {
        icon: 'branding_watermark',
        label: 'Marca',
        value: paymentMethod.brand,
      },
      {
        icon: 'pin',
        label: 'Últimos 4 dígitos',
        value: paymentMethod.last4,
      },
    ];

    if (paymentMethod.funding) {
      items.push({
        icon: 'account_balance_wallet',
        label: 'Funding',
        value: paymentMethod.funding,
      });
    }

    return items;
  }

  private oxxoItems(paymentMethod: OxxoPaymentMethod): InfoCardItemConfig[] {
    return [
      {
        icon: 'store',
        label: 'Tipo',
        value: 'OXXO',
      },
      {
        icon: 'tag',
        label: 'Referencia',
        value: paymentMethod.reference,
      },
      {
        icon: 'schedule',
        label: 'Expira',
        value: paymentMethod.expires_after,
      },
    ];
  }

  private speiItems(paymentMethod: SpeiPaymentMethod): InfoCardItemConfig[] {
    return [
      {
        icon: 'account_balance',
        label: 'Tipo',
        value: 'Transferencia SPEI',
      },
      {
        icon: 'account_balance',
        label: 'Banco',
        value: paymentMethod.bank_name,
      },
      {
        icon: 'numbers',
        label: 'CLABE',
        value: paymentMethod.clabe,
      },
      {
        icon: 'tag',
        label: 'Referencia',
        value: paymentMethod.reference,
      },
    ];
  }
}
