import { Component, Input } from '@angular/core';
import { PaymentIntentCancelledMetadataResponse } from '../../../../../models/response/events/payment/metadata/payment-intent-cancelled-metadata-response.model';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';

@Component({
  selector: 'app-payment-cancelled-metadata',
  standalone: true,
  imports: [MetadataCardComponent, MetadataRowComponent],
  templateUrl: './payment-cancelled-metadata.component.html',
  styleUrl: './payment-cancelled-metadata.component.scss',
})
export class PaymentCancelledMetadataComponent {
  @Input({ required: true }) metadata!: PaymentIntentCancelledMetadataResponse;
}
