import { Component, Input, Type } from '@angular/core';
import { ReconcileEventByIdResponse } from '../../../../../models/response/events/reconciliation/reconcile-event-by-id.response';
import { CommonModule } from '@angular/common';
import { ReconciliationOutcome } from '../../../../../models/request/events/reconciliation/reconciliation-outcome.enum';
import { ReconciliationCorrectedMetadataComponent } from '../reconciliation-corrected-metadata/reconciliation-corrected-metadata.component';
import { ReconciliationMatchedMetadataComponent } from '../reconciliation-matched-metadata/reconciliation-matched-metadata.component';

@Component({
  selector: 'app-reconciliation-event-metadata',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './reconciliation-event-metadata.component.html',
  styleUrl: './reconciliation-event-metadata.component.scss',
})
export class ReconciliationEventMetadataComponent {
  @Input({ required: true }) reconcileEvent!: ReconcileEventByIdResponse;

  eventMap: Partial<Record<ReconciliationOutcome, Type<any>>> = {
    [ReconciliationOutcome.CORRECTED]: ReconciliationCorrectedMetadataComponent,
    [ReconciliationOutcome.MATCHED]: ReconciliationMatchedMetadataComponent,
  };

  get component(): Type<any> | undefined {
    const outcome = this.reconcileEvent.outcome;

    if (!outcome) {
      return undefined;
    }

    return this.eventMap[outcome];
  }
}
