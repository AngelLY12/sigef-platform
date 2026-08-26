import { Component, Input } from '@angular/core';
import { MetadataBadgeComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { CurrencyMXNPipe } from '../../../../../../../shared/pipes/currency-mxn.pipe';
import { PaymentRequiresActionMetadataResponse } from '../../../../../models/response/events/email/metadata/payment-requires-action-metadata-response.model';

@Component({
  selector: 'app-payment-requires-action-metadata',
  standalone: true,
  imports: [
    MetadataBadgeComponent,
    MetadataCardComponent,
    MetadataRowComponent,
    CurrencyMXNPipe
  ],
  templateUrl: './payment-requires-action-metadata.component.html',
  styleUrl: './payment-requires-action-metadata.component.scss'
})
export class PaymentRequiresActionMetadataComponent {
  @Input({ required: true}) metadata!: PaymentRequiresActionMetadataResponse;
}
