export interface ConceptsListResponse {
  id: number;
  concept_name: string;
  amount: string;
  description: string;
  status: string;
  applies_to: string;
  expiration_human: string;
  days_until_deletion: number;
  has_expiration: boolean;
  is_deleted: boolean;
}
