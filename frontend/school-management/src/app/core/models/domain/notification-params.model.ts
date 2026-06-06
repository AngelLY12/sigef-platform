export interface NotificationParams {
  perPage: number;
  page: number;
  forceRefresh?: boolean;
}

const BASE_NOTIFICATIONS_PARAMS: Readonly<NotificationParams> = {
  perPage: 15,
  page: 1,
  forceRefresh: false,
};

export function createNotificationsParams(
  overrides: Partial<NotificationParams> = {}
): NotificationParams {
  return {
    ...BASE_NOTIFICATIONS_PARAMS,
    ...overrides
  };
}
