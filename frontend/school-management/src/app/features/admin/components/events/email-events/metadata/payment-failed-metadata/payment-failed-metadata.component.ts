import { Component, Input } from '@angular/core';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { MetadataBadgeComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { PaymentFailedMetadataResponse } from '../../../../../models/response/events/email/metadata/payment-failed-metadata-response.model';
import { CurrencyMXNPipe } from '../../../../../../../shared/pipes/currency-mxn.pipe';

@Component({
  selector: 'app-payment-failed-metadata',
  standalone: true,
  imports: [
    MetadataCardComponent,
    MetadataRowComponent,
    CurrencyMXNPipe
  ],
  templateUrl: './payment-failed-metadata.component.html',
  styleUrl: './payment-failed-metadata.component.scss'
})
export class PaymentFailedMetadataComponent {
  @Input({ required: true }) metadata!: PaymentFailedMetadataResponse;
}
