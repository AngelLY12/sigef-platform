import { EmailEventSourceType } from "./email-event-source-type.enum";
import { EmailEventStatus } from "./email-event-status.enum";
import { EmailEventType } from "./email-event-type.enum";

export interface EmailEventHistoryFilters {
  forceRefresh: boolean;
  page: number;
  perPage: number;
  eventType: EmailEventType | null;
  status: EmailEventStatus | null;
  sourceType: EmailEventSourceType | null;
  from: string | null;
  to: string | null;
}


export const BASE_EMAIL_EVENT_HISTORY_FILTERS: Readonly<EmailEventHistoryFilters> = {
  forceRefresh: false,
  page: 1,
  perPage: 15,
  eventType: null,
  status: null,
  sourceType: null,
  from: null,
  to: null,
};