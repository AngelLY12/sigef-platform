import { PaymentStatus } from "../../../../core/models/enums/payment-status.enum";

export interface PaymentHistoryItem {
  id: number;
  concept: string;
  amount: string;
  amount_received: string;
  status: PaymentStatus;
  date: string;
}
