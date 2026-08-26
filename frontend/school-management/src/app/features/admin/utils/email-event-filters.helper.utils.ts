import { EmailEventFilters } from "../models/request/events/email/email-event-filters.model";

export class EmailEventFiltersHelper {

  static changeUser(
    params: EmailEventFilters,
    userId: number | null,
  ): EmailEventFilters {
    return {
      ...params,
      userId,
      page: 1,
    };
  }


  static changeRecipientEmail(
    params: EmailEventFilters,
    recipientEmail: string | null,
  ): EmailEventFilters {
    return {
      ...params,
      recipientEmail: recipientEmail?.trim() || null,
      page: 1,
    };
  }

  static changeSourceId(
    params: EmailEventFilters,
    sourceId: string | null,
  ): EmailEventFilters {
    return {
      ...params,
      sourceId: sourceId?.trim() || null,
      page: 1,
    };
  }
}