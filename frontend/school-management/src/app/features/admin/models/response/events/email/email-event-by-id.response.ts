import { EmailEventSourceType } from "../../../request/events/email/email-event-source-type.enum";
import { EmailEventStatus } from "../../../request/events/email/email-event-status.enum";
import { EmailEventType } from "../../../request/events/email/email-event-type.enum";
import { EmailEventMetadataResponse } from "./metadata/email-event-metadata-response.model";

export interface EmailEventByIdResponse {
  id: number;
  userId: number | null;
  eventType: EmailEventType;
  eventTypeLabel: string;
  recipientEmail: string;
  status: EmailEventStatus;
  statusLabel: string;
  sourceType: EmailEventSourceType;
  sourceTypeLabel: string;
  sourceId: string;
  attemptCount: number;
  errorMessage: string | null;
  sentAt: string | null;
  deliveredAt: string | null;
  failedAt: string | null;
  metadata: EmailEventMetadataResponse | null;
  createdAt: string | null;
  updatedAt: string | null;
}
