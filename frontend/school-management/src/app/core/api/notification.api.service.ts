import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { NotificationParams } from '../models/domain/notification-params.model';
import { map, Observable } from 'rxjs';
import { Paginated } from '../utils/paginated-helper.utils';
import { NOTIFICATIONS_URL } from '../constants/api.constants';
import { ApiSuccessResponse } from '../models/api-success-response.model';
import { Notification } from '../models/domain/notification.model';
import { ReadNotificationsPaginatedResponse } from '../models/responses/notifications-response.model';
import { PaginatedResponse } from '../models/domain/paginated-response.model';

@Injectable({ providedIn: 'root' })
export class NotificationService {
  private http = inject(HttpClient);

  getAllNotifications(
    params: NotificationParams,
  ): Observable<ReadNotificationsPaginatedResponse> {
    const { page, perPage, forceRefresh } = params;

    const url = `${NOTIFICATIONS_URL}?page=${page}&perPage=${perPage} ${forceRefresh ? `&forceRefresh=true` : ''}`;

    return this.http.get<ApiSuccessResponse<{ notifications: PaginatedResponse<Notification>, unread_count: number, read_count: number }>>(url).pipe(
      map((res) => {
        return {
          notifications: new Paginated(res.data.notifications),
          unread_count: res.data.unread_count,
          read_count: res.data.read_count,
        };
      }),
    );
  }

  getUnreadNotifications(params: NotificationParams): Observable<Paginated<Notification>> {
    const { page, perPage, forceRefresh } = params;

    const url = `${NOTIFICATIONS_URL}/unread?page=${page}&perPage=${perPage} ${forceRefresh ? `&forceRefresh=true` : ''}`;

    return this.http
      .get<
        ApiSuccessResponse<{ notifications: PaginatedResponse<Notification> }>
      >(url)
      .pipe(map((res) => new Paginated(res.data.notifications)));
  }

  markAsRead(notificationId: string): Observable<void> {
    return this.http
      .post(`${NOTIFICATIONS_URL}/mark-as-read/${notificationId}`, {})
      .pipe(map(() => {}));
  }

  markAllAsRead(): Observable<void> {
    return this.http.post(`${NOTIFICATIONS_URL}/mark-as-read`, {}).pipe(map(() => {}));
  }

  deleteNotification(notificationId: string): Observable<void> {
    return this.http.delete(`${NOTIFICATIONS_URL}/${notificationId}`).pipe(map(() => {}));
  }

}
