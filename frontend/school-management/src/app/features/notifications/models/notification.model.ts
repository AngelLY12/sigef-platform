import { NotificationPayload } from "./notification-payload.model";

export interface Notification {
  id: string;
  type: string;
  notifiable_type: string;
  notifiable_id: number;
  data: NotificationPayload;
  read_at: string | null;
  created_at: string;
  updated_at?: string;
}
