import { ChargeSucceededMetadataResponse } from './charge-succeeded-metadata-response.model';
import { CheckoutSessionMetadataResponse } from './checkout-session-metadata-response.model';
import { PaymentIntentCancelledMetadataResponse } from './payment-intent-cancelled-metadata-response.model';
import { PaymentIntentFailedMetadataResponse } from './payment-intent-failed-metadata-response.model';
import { PaymentIntentRequiresActionMetadataResponse } from './payment-intent-requires-action-metadata-response.model';
import { PaymentIntentSucceededMetadataResponse } from './payment-intent-succeeded-metadata-response.model';

export type PaymentEventMetadataResponse =
  | ChargeSucceededMetadataResponse
  | CheckoutSessionMetadataResponse
  | PaymentIntentCancelledMetadataResponse
  | PaymentIntentFailedMetadataResponse
  | PaymentIntentRequiresActionMetadataResponse
  | PaymentIntentSucceededMetadataResponse;
