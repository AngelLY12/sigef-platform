export interface PaymentsResponse {
  id: number;
  date: string;
  concept: string;
  amount: string;
  amount_received: string;
  method: string;
  userId: number;
  fullName: string;
}
