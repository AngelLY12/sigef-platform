import { Notification } from "../models/domain/notification.model";
import { PaginatedResponse } from "../models/domain/paginated-response.model";
import { NotificationsResponse } from "../models/responses/notifications-response.model";

export function mapLaravelPagination<T>(res: any): PaginatedResponse<T> {
  return {
    items: res.data,
    currentPage: res.current_page,
    lastPage: res.last_page,
    perPage: res.per_page,
    total: res.total,
    hasMorePages: res.current_page < res.last_page,
    nextPage: res.current_page < res.last_page ? res.current_page + 1 : null,
    previousPage: res.current_page > 1 ? res.current_page - 1 : null
  };
}

export function mapNotificationsPagination(
  res: NotificationsResponse
): PaginatedResponse<Notification> {

  const n = res.notifications;

  return {
    items: n.data,
    currentPage: n.current_page,
    lastPage: n.last_page,
    perPage: n.per_page,
    total: n.total,
    hasMorePages: n.current_page < n.last_page,
    nextPage: n.current_page < n.last_page ? n.current_page + 1 : null,
    previousPage: n.current_page > 1 ? n.current_page - 1 : null
  };
}

