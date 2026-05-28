export interface ConceptDetailResponse {
  id: number;
  concept_name: string;
  status: string;
  start_date: string;
  amount: string;
  applies_to: string;
  description: string;
  end_date: string;
  deleted_at: string;
  created_at_human: string;
  updated_at_human: string;
  deleted_at_human: string;
  days_until_deletion: number;
  expiration_human: string;
  expiration_info: {
    text: string;
    days: number;
    is_expired: boolean;
    is_today: boolean;
    urgency: string;
    date_formatted: string;
    date_short: string;
  };
}
