export interface PaymentsResponse {
  payments_data: PaymentsData;
}

export interface PaymentsData {
  totalPayments: string;
  totalPayouts: string;
  totalFees: string;
  totalNetReceived: string;
  totalNetAfterFees: string;

  paymentsBySemester: Record<string, SemesterGroup>;
  payoutsBySemester: Record<string, SemesterGroup>;
  feesBySemester: Record<string, SemesterGroup>;

  totalBalanceAvailable: string;
  totalBalancePending: string;

  availablePercentage: string;
  pendingPercentage: string;
  netReceivedPercentage: string;
  feePercentage: string;
  netAfterFeesPercentage: string;

  totalBalanceAvailableBySource: Record<string, string>;
  totalBalancePendingBySource: Record<string, string>;
}

export interface SemesterGroup {
  total: string;
  months: string[];
}
