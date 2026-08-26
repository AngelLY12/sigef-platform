import { Component, Input, Type } from '@angular/core';
import { EmailEventByIdResponse } from '../../../../../models/response/events/email/email-event-by-id.response';
import { CommonModule } from '@angular/common';
import { EmailEventType } from '../../../../../models/request/events/email/email-event-type.enum';
import { ConceptCreatedMetadataComponent } from '../concept-created-metadata/concept-created-metadata.component';
import { ConceptCriticalAmountMetadataComponent } from '../concept-critical-amount-metadata/concept-critical-amount-metadata.component';
import { PaymentCreatedMetadataComponent } from '../payment-created-metadata/payment-created-metadata.component';
import { PaymentFailedMetadataComponent } from '../payment-failed-metadata/payment-failed-metadata.component';
import { PaymentRequiresActionMetadataComponent } from '../payment-requires-action-metadata/payment-requires-action-metadata.component';
import { PaymentValidatedMetadataComponent } from '../payment-validated-metadata/payment-validated-metadata.component';
import { UserCreatedMetadataComponent } from '../user-created-metadata/user-created-metadata.component';

@Component({
  selector: 'app-email-event-metadata',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './email-event-metadata.component.html',
  styleUrl: './email-event-metadata.component.scss'
})
export class EmailEventMetadataComponent {
  @Input({ required: true }) emailEvent!: EmailEventByIdResponse;

  emailEventMap: Record<EmailEventType, Type<any>> = {
    [EmailEventType.CONCEPT_CREATED]: ConceptCreatedMetadataComponent,
    [EmailEventType.CONCEPT_CRITICAL_AMOUNT_ALERT]: ConceptCriticalAmountMetadataComponent,
    [EmailEventType.PAYMENT_CREATED]: PaymentCreatedMetadataComponent,
    [EmailEventType.PAYMENT_FAILED]: PaymentFailedMetadataComponent,
    [EmailEventType.PAYMENT_REQUIRES_ACTION]: PaymentRequiresActionMetadataComponent,
    [EmailEventType.PAYMENT_VALIDATED]: PaymentValidatedMetadataComponent,
    [EmailEventType.USER_CREATED]: UserCreatedMetadataComponent,
  };

  get component() {
    return this.emailEventMap[this.emailEvent.eventType];
  }

}
