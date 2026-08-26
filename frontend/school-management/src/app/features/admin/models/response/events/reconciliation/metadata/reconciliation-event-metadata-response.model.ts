import { ReconciliationCorrectedMetadataResponse } from './reconciliation-corrected-metadata-response.model';
import { ReconciliationMatchedMetadataResponse } from './reconciliation-matched-metadata-response.model';

export type ReconciliationEventMetadataResponse =
  | ReconciliationCorrectedMetadataResponse
  | ReconciliationMatchedMetadataResponse;
