export interface StripePaymentsResponse {
  customer_name: string | null;
  concept_name: string | null;
  payment_id: number | null;
  user_id: number | null;
  concept_id: number | null;
  paid: boolean;
  status: string | null;
  amount: string | null;
  amount_received: string | null;
  created: string | null;
  receipt_url: string | null;
  payment_method_type: string | null;
}
