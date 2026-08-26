import { Component, Input } from '@angular/core';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { PaymentIntentRequiresActionMetadataResponse } from '../../../../../models/response/events/payment/metadata/payment-intent-requires-action-metadata-response.model';

@Component({
  selector: 'app-payment-requires-action-metadata',
  standalone: true,
  imports: [MetadataCardComponent, MetadataRowComponent],
  templateUrl: './payment-requires-action-metadata.component.html',
  styleUrl: './payment-requires-action-metadata.component.scss'
})
export class PaymentRequiresActionMetadataComponent {
  @Input({ required: true }) metadata!: PaymentIntentRequiresActionMetadataResponse;
}
