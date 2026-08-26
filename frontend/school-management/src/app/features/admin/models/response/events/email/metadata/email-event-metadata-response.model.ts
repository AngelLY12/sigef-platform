import { ConceptCreatedMetadataResponse } from "./concept-created-metadata-response.model";
import { ConceptCriticalAmountAlertMetadataResponse } from "./concept-critical-amount-alert-metadata-response.model";
import { PaymentCreatedMetadataResponse } from "./payment-created-metadata-response.model";
import { PaymentFailedMetadataResponse } from "./payment-failed-metadata-response.model";
import { PaymentRequiresActionMetadataResponse } from "./payment-requires-action-metadata-response.model";
import { PaymentValidatedMetadataResponse } from "./payment-validated-metadata-response.model";
import { UserCreatedMetadataResponse } from "./user-created-metadata-response.model";

export type EmailEventMetadataResponse =
  | ConceptCreatedMetadataResponse
  | ConceptCriticalAmountAlertMetadataResponse
  | PaymentCreatedMetadataResponse
  | PaymentFailedMetadataResponse
  | PaymentRequiresActionMetadataResponse
  | PaymentValidatedMetadataResponse
  | UserCreatedMetadataResponse;
