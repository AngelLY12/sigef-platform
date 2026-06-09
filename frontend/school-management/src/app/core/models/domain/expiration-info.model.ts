export interface ExpirationInfo {
  text: string;
  days: number;
  is_expired: boolean;
  is_today: boolean;
  urgency: string;
  date_formatted: string;
  date_short: string;
}
