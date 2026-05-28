import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { NotificationParams } from '../models/domain/notification-params.model';
import { map, Observable } from 'rxjs';
import { Paginated } from '../utils/paginated-helper.utils';
import {
  NotificationsPaginatedResponse,
  NotificationsResponse,
  UnreadNotificationsResponse,
} from '../models/responses/notifications-response.model';
import { NOTIFICATIONS_URL } from '../constants/api.constants';
import { ApiSuccessResponse } from '../models/api-success-response.model';
import { mapNotificationsPagination } from '../utils/pagination.adapter.utils';
import { Notification } from '../models/domain/notification.model';

@Injectable({ providedIn: 'root' })
export class NotificationService {
  private http = inject(HttpClient);

  getAllNotifications(
    params: NotificationParams,
  ): Observable<NotificationsPaginatedResponse> {
    const { page, perPage } = params;

    const url = `${NOTIFICATIONS_URL}?page=${page}&per_page=${perPage}`;

    return this.http.get<ApiSuccessResponse<NotificationsResponse>>(url).pipe(
      map((res) => {
        const pagination = mapNotificationsPagination(res.data);

        return {
          notifications: new Paginated<Notification>(pagination),
          unreadCount: res.data.unread_count,
          readCount: res.data.read_count,
        };
      }),
    );
  }

  getUnreadNotifications(): Observable<UnreadNotificationsResponse> {
    return this.http
      .get<
        ApiSuccessResponse<UnreadNotificationsResponse>
      >(`${NOTIFICATIONS_URL}/unread`)
      .pipe(map((res) => res.data));
  }

}
