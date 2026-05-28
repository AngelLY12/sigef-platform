export interface NotificationParams {
  perPage: number;
  page: number;
}

const BASE_NOTIFICATIONS_PARAMS: Readonly<NotificationParams> = {
  perPage: 15,
  page: 1,
};

export function createNotificationsParams(
  overrides: Partial<NotificationParams> = {}
): NotificationParams {
  return {
    ...BASE_NOTIFICATIONS_PARAMS,
    ...overrides
  };
}
