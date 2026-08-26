import { Component, Input } from '@angular/core';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { CurrencyMXNPipe } from '../../../../../../../shared/pipes/currency-mxn.pipe';
import { PaymentValidatedMetadataResponse } from '../../../../../models/response/events/email/metadata/payment-validated-metadata-response.model';
import { MetadataBadgeComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { MetadataLinkComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-link/metadata-link.component';

@Component({
  selector: 'app-payment-validated-metadata',
  standalone: true,
  imports: [
    MetadataCardComponent,
    MetadataRowComponent,
    MetadataLinkComponent,
    CurrencyMXNPipe,
    MetadataBadgeComponent
  ],
  templateUrl: './payment-validated-metadata.component.html',
  styleUrl: './payment-validated-metadata.component.scss'
})
export class PaymentValidatedMetadataComponent {
  @Input({ required: true }) metadata!: PaymentValidatedMetadataResponse;
}
