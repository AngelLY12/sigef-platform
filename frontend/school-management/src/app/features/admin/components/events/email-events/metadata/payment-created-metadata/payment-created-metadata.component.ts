import { Component, Input } from '@angular/core';
import { PaymentCreatedMetadataResponse } from '../../../../../models/response/events/email/metadata/payment-created-metadata-response.model';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { CurrencyMXNPipe } from '../../../../../../../shared/pipes/currency-mxn.pipe';
import { MetadataLinkComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-link/metadata-link.component';

@Component({
  selector: 'app-payment-created-metadata',
  standalone: true,
  imports: [
    MetadataCardComponent,
    MetadataRowComponent,
    MetadataLinkComponent,
    CurrencyMXNPipe
  ],
  templateUrl: './payment-created-metadata.component.html',
  styleUrl: './payment-created-metadata.component.scss'
})
export class PaymentCreatedMetadataComponent {
  @Input({ required: true }) metadata!: PaymentCreatedMetadataResponse;

}
