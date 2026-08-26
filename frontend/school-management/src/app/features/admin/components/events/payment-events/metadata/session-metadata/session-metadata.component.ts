import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { PaymentEventType } from '../../../../../models/request/events/payment/payment-event-type.enum';
import { CheckoutSessionMetadataResponse } from './../../../../../models/response/events/payment/metadata/checkout-session-metadata-response.model';
import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-session-metadata',
  imports: [MetadataCardComponent, MetadataRowComponent],
  templateUrl: './session-metadata.component.html',
  styleUrl: './session-metadata.component.scss',
})
export class SessionMetadataComponent {
  @Input({ required: true }) metadata!: CheckoutSessionMetadataResponse;
  @Input({ required: true }) type!: PaymentEventType;

  title(): string {
    switch (this.type) {
      case PaymentEventType.WEBHOOK_SESSION_ASYNC_COMPLETED:
        return 'Sesión de pago procesada';
      case PaymentEventType.WEBHOOK_SESSION_COMPLETED:
        return 'Sesión de pago completada';
      case PaymentEventType.WEBHOOK_SESSION_EXPIRED:
        return 'Sesión de pago expirada';
      default:
        return 'Sesión de pago';
    }
  }
}
