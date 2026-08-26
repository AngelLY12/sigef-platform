import { Component, Input } from '@angular/core';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { ConceptCreatedMetadataResponse } from '../../../../../models/response/events/email/metadata/concept-created-metadata-response.model';
import { CurrencyMXNPipe } from '../../../../../../../shared/pipes/currency-mxn.pipe';

@Component({
  selector: 'app-concept-created-metadata',
  standalone: true,
  imports: [
    MetadataCardComponent,
    MetadataRowComponent,
    CurrencyMXNPipe
  ],
  templateUrl: './concept-created-metadata.component.html',
  styleUrl: './concept-created-metadata.component.scss'
})
export class ConceptCreatedMetadataComponent {
  @Input({ required: true })
  metadata!: ConceptCreatedMetadataResponse;

}
