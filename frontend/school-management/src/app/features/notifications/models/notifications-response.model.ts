import { Paginated } from "../../../core/utils/paginated-helper.utils";
import { Notification } from "./notification.model";


export interface ReadNotificationsPaginatedResponse {
  notifications: Paginated<Notification>;
  unread_count: number;
  read_count: number;
}
