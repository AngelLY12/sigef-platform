import { Paginated } from "../../utils/paginated-helper.utils";
import { Notification } from "../domain/notification.model";


export interface ReadNotificationsPaginatedResponse {
  notifications: Paginated<Notification>;
  unread_count: number;
  read_count: number;
}
