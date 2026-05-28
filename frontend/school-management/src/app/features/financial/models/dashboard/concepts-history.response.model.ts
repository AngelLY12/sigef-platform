import { ConceptAppliesTo } from "../../../../core/models/enums/applies-to-concepts.enum";
import { PaymentConceptStatus } from "../../../../core/models/enums/payment-concepts-status.enum";

export interface ConceptsHistoryItems {
  id: number;
  concept_name: string;
  status: PaymentConceptStatus;
  amount: string;
  applies_to: ConceptAppliesTo;
  start_date: string;
  end_date: string;
  expiration_human: string;
}
