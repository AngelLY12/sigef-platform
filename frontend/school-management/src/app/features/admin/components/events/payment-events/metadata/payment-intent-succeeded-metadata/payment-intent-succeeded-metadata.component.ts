import { Component, Input } from '@angular/core';
import { MetadataBadgeComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { PaymentIntentSucceededMetadataResponse } from '../../../../../models/response/events/payment/metadata/payment-intent-succeeded-metadata-response.model';

@Component({
  selector: 'app-payment-intent-succeeded-metadata',
  standalone: true,
  imports: [MetadataBadgeComponent, MetadataCardComponent, MetadataRowComponent],
  templateUrl: './payment-intent-succeeded-metadata.component.html',
  styleUrl: './payment-intent-succeeded-metadata.component.scss'
})
export class PaymentIntentSucceededMetadataComponent {
  @Input({ required: true }) metadata!: PaymentIntentSucceededMetadataResponse;
}
