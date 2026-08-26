import { Component, Input } from '@angular/core';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { ReconciliationMatchedMetadataResponse } from '../../../../../models/response/events/reconciliation/metadata/reconciliation-matched-metadata-response.model';

@Component({
  selector: 'app-reconciliation-matched-metadata',
  imports: [MetadataCardComponent, MetadataRowComponent,],
  templateUrl: './reconciliation-matched-metadata.component.html',
  styleUrl: './reconciliation-matched-metadata.component.scss'
})
export class ReconciliationMatchedMetadataComponent {
  @Input({ required: true }) metadata!: ReconciliationMatchedMetadataResponse;
}
