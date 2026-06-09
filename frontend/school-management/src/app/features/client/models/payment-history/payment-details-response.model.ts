import { PaymentMethodDetails } from "../../../../core/models/domain/payment-method-details-type.model";

export interface PaymentDetailsResponse {
  id: number;
  concept_name: string;
  amount: string;
  status: string;
  created_at_human: string;
  has_pending_amount: boolean;
  balance: string;
  payment_method_details: PaymentMethodDetails;
  amount_received: string;
  reference: string;
  url: string;
}
