import { ChargeSucceededMetadataComponent } from './../charge-succeeded-metadata/charge-succeeded-metadata.component';
import { PaymentEventType } from './../../../../../models/request/events/payment/payment-event-type.enum';
import { Component, Input, Type } from '@angular/core';
import { PaymentEventByIdResponse } from '../../../../../models/response/events/payment/payment-event-by-id.response';
import { PaymentCancelledMetadataComponent } from '../payment-cancelled-metadata/payment-cancelled-metadata.component';
import { PaymentFailedMetadataComponent } from '../payment-failed-metadata/payment-failed-metadata.component';
import { PaymentRequiresActionMetadataComponent } from '../payment-requires-action-metadata/payment-requires-action-metadata.component';
import { SessionMetadataComponent } from '../session-metadata/session-metadata.component';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-payment-event-metadata',
  imports: [CommonModule],
  templateUrl: './payment-event-metadata.component.html',
  styleUrl: './payment-event-metadata.component.scss'
})
export class PaymentEventMetadataComponent {
  @Input({ required: true }) paymentEvent!: PaymentEventByIdResponse;

  paymentEventMap: Record<PaymentEventType, Type<any>> = {
    [PaymentEventType.WEBHOOK_CHARGE_SUCCEEDED]: ChargeSucceededMetadataComponent,
    [PaymentEventType.WEBHOOK_PAYMENT_CANCELLED]: PaymentCancelledMetadataComponent,
    [PaymentEventType.WEBHOOK_PAYMENT_FAILED]: PaymentFailedMetadataComponent,
    [PaymentEventType.WEBHOOK_PAYMENT_INTENT_SUCCEEDED]: PaymentFailedMetadataComponent,
    [PaymentEventType.WEBHOOK_PAYMENT_REQUIRES_ACTION]: PaymentRequiresActionMetadataComponent,
    [PaymentEventType.WEBHOOK_SESSION_ASYNC_COMPLETED]: SessionMetadataComponent,
    [PaymentEventType.WEBHOOK_SESSION_COMPLETED]: SessionMetadataComponent,
        [PaymentEventType.WEBHOOK_SESSION_EXPIRED]: SessionMetadataComponent,
  };

  get component() {
    return this.paymentEventMap[this.paymentEvent.eventType];
  }


}
