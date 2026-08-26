import { Component, Input } from '@angular/core';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { ReconciliationCorrectedMetadataResponse } from '../../../../../models/response/events/reconciliation/metadata/reconciliation-corrected-metadata-response.model';
import { CurrencyMXNPipe } from '../../../../../../../shared/pipes/currency-mxn.pipe';

@Component({
  selector: 'app-reconciliation-corrected-metadata',
  standalone: true,
  imports: [MetadataCardComponent, MetadataRowComponent, CurrencyMXNPipe],
  templateUrl: './reconciliation-corrected-metadata.component.html',
  styleUrl: './reconciliation-corrected-metadata.component.scss'
})
export class ReconciliationCorrectedMetadataComponent {
  @Input({ required: true }) metadata!: ReconciliationCorrectedMetadataResponse;
}
