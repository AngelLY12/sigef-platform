export interface EmailEventResponse {
  id: number;
  userId: number | null;
  userName: string | null;
  eventType: string;
  status: string;
  recipientEmail: string;
  createdAt: string;
}
