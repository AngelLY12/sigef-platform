import { ReconciliationEventStatus } from "../../../request/events/reconciliation/reconciliation-event-status.enum";
import { ReconciliationOutcome } from "../../../request/events/reconciliation/reconciliation-outcome.enum";
import { ReconciliationSourceType } from "../../../request/events/reconciliation/reconciliation-source-type.enum";
import { ReconciliationEventMetadataResponse } from "./metadata/reconciliation-event-metadata-response.model";

export interface ReconcileEventByIdResponse {
  id: number | null;
  paymentId: number | null;
  outcome: ReconciliationOutcome | null;
  outcomeLabel: string | null;
  status: ReconciliationEventStatus;
  statusLabel: string;
  sourceType: ReconciliationSourceType;
  sourceTypeLabel: string;
  sourceId: string;
  errorMessage: string | null;
  metadata: ReconciliationEventMetadataResponse | null;
  startedAt: string | null;
  completedAt: string | null;
  failedAt: string | null;
  createdAt: string | null;
  updatedAt: string | null;
}
