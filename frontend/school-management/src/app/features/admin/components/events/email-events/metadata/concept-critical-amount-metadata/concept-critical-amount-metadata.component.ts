import { ConceptCriticalAmountAlertMetadataResponse } from './../../../../../models/response/events/email/metadata/concept-critical-amount-alert-metadata-response.model';
import { Component, Input } from '@angular/core';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataBadgeComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { CurrencyMXNPipe } from '../../../../../../../shared/pipes/currency-mxn.pipe';

@Component({
  selector: 'app-concept-critical-amount-metadata',
  standalone: true,
  imports: [
    MetadataCardComponent,
    MetadataBadgeComponent,
    MetadataRowComponent,
    CurrencyMXNPipe
  ],
  templateUrl: './concept-critical-amount-metadata.component.html',
  styleUrl: './concept-critical-amount-metadata.component.scss'
})
export class ConceptCriticalAmountMetadataComponent {
  @Input({ required: true }) metadata!: ConceptCriticalAmountAlertMetadataResponse;
}
