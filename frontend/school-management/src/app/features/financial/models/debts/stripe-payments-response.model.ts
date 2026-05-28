export interface StripePaymentsResponse {
  id: string;
  payment_intent_id: string;
  concept_name: string;
  status: string;
  amount_total: string;
  amount_received: string;
  created: string;
  receipt_url: string;
}
