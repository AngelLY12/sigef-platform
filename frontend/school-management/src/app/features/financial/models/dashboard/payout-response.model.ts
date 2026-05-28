export interface PayoutResponse {
  payout: Payout;
}

export interface Payout {
  payout_id: string;
  amount: string;
  currency: string;
  arrival_date: string;
  status: string;
  available_before_payout: string;
}
