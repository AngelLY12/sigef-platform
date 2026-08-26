import { EmailEventSourceType } from "./email-event-source-type.enum";
import { EmailEventStatus } from "./email-event-status.enum";
import { EmailEventType } from "./email-event-type.enum";

export interface EmailEventFilters {
  forceRefresh: boolean;
  page: number;
  perPage: number;
  userId: number | null;
  eventType: EmailEventType | null;
  status: EmailEventStatus | null;
  recipientEmail: string | null;
  sourceType: EmailEventSourceType | null;
  sourceId: string | null;
  from: string | null;
  to: string | null;
}

export const BASE_EMAIL_EVENT_FILTERS: Readonly<EmailEventFilters> = {
  forceRefresh: false,
  page: 1,
  perPage: 15,
  userId: null,
  eventType: null,
  status: null,
  recipientEmail:null,
  sourceType: null,
  sourceId: null,
  from: null,
  to: null,
};