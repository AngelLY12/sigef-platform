export interface ConceptRelationsResponse {
  id: number;
  concept_name: string;
  applies_to: string;
  users: string[];
  careers: number[];
  semesters: number[];
  exceptionUsers: string[];
  applicantTags: string[];
}
