import { ConceptAppliesTo } from "../../../../core/models/enums/applies-to-concepts.enum";
import { PaymentConceptApplicantTags } from "../../../../core/models/enums/payment-concept-applicant-tags.enum";
import { PaymentConceptStatus } from "../../../../core/models/enums/payment-concepts-status.enum";

export interface ConceptsCreateRequest {
  concept_name: string;
  description?: string;
  status: PaymentConceptStatus;
  start_date: string;
  end_date?: string;
  amount: string;
  applies_to: ConceptAppliesTo;
  semestres?: number[];
  careers?: number[];
  students?: string[];
  exceptionStudents?: string[];
  applicantTags?: PaymentConceptApplicantTags[];
}
