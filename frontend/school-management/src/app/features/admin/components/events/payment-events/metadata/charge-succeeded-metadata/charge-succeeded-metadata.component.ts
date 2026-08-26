import { Component, Input } from '@angular/core';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataLinkComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-link/metadata-link.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { ChargeSucceededMetadataResponse } from '../../../../../models/response/events/payment/metadata/charge-succeeded-metadata-response.model';
import { CurrencyMXNPipe } from '../../../../../../../shared/pipes/currency-mxn.pipe';

@Component({
  selector: 'app-charge-succeeded-metadata',
  standalone: true,
  imports: [
    MetadataCardComponent,
    MetadataLinkComponent,
    MetadataRowComponent,
    CurrencyMXNPipe,
  ],
  templateUrl: './charge-succeeded-metadata.component.html',
  styleUrl: './charge-succeeded-metadata.component.scss'
})
export class ChargeSucceededMetadataComponent {
   @Input({ required: true }) metadata!: ChargeSucceededMetadataResponse;
}
