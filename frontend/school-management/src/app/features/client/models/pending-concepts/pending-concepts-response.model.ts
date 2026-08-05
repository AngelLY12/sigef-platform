export interface PendingConceptsResponse {
  id: number;
  concept_name: string;
  description: string;
  amount: string;
  start_date: string;
  end_date: string;
  expiration_human: string;
  expiration_info: ExpirationInfo;
}

export interface ExpirationInfo {
  text: string;
  days: number;
  is_expired: boolean;
  is_today: boolean;
  urgency: string;
  date_formatted: string;
  date_short: string;
}
