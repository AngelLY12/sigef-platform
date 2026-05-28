import { Paginated } from "../../utils/paginated-helper.utils";
import { Notification } from "../domain/notification.model";

export interface NotificationsResponse {
  notifications: {
    data: Notification[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  unread_count: number;
  read_count: number;
}

export interface NotificationsPaginatedResponse {
  notifications: Paginated<Notification>;
  unreadCount: number;
  readCount: number;
}

export interface UnreadNotificationsResponse {
  notifications: Notification[];
  count: number;
}
