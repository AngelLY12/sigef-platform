import { Component, Input } from '@angular/core';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataBadgeComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { PaymentIntentFailedMetadataResponse } from '../../../../../models/response/events/payment/metadata/payment-intent-failed-metadata-response.model';

@Component({
  selector: 'app-payment-failed-metadata',
  standalone: true,
  imports: [MetadataCardComponent, MetadataBadgeComponent, MetadataRowComponent],
  templateUrl: './payment-failed-metadata.component.html',
  styleUrl: './payment-failed-metadata.component.scss'
})
export class PaymentFailedMetadataComponent {
  @Input({ required: true }) metadata!: PaymentIntentFailedMetadataResponse;

}
