export interface PaymentsByStudentResponse {
  userId: number;
  fullName: string;
  n_control: string;
  semestre: number;
  career_name: string;
  num_pending: number;
  num_expired: number;
  total_amount_pending: string;
  total_paid: string;
  expired_amount: string;
  num_paid: number;
}
