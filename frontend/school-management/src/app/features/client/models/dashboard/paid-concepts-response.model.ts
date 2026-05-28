export interface PaidData {
  totalPayments: string;
  paymentsByMonth: Record<string, string>;
}

export interface PaidResponse {
  paid_data: PaidData;
}
