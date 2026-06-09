export interface PaymentsByConceptResponse {
  concept_name: string;
  amount_total: string;
  amount_received_total: string;
  first_payment_date: string;
  last_payment_date: string;
  collection_rate: string;
}
