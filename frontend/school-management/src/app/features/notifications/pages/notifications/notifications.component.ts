import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../shared/components/layout/page-layout/page-layout.component';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { NotificationService } from '../../../../core/api/notifications/notification.api.service';
import {
  createNotificationsParams,
  NotificationParams,
} from '../../models/notification-params.model';
import { Notification } from '../../models/notification.model';
import { ListController } from '../../../../core/utils/list-controller.utils';
import { Paginated } from '../../../../core/utils/paginated-helper.utils';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { forkJoin } from 'rxjs';
import { ModalService } from '../../../../core/services/modal.service';
import { NotificationsListComponent } from '../../components/notifications-list/notifications-list.component';
import { FolderTab } from '../../../../shared/components/navigation/folder-tabs/folder-tabs-config.model';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { FolderTabsComponent } from '../../../../shared/components/navigation/folder-tabs/folder-tabs.component';

@Component({
  selector: 'app-notifications',
  standalone: true,
  imports: [
    PageLayoutComponent,
    CommonModule,
    NotificationsListComponent,
    FolderTabsComponent,
    ButtonComponent,
  ],
  templateUrl: './notifications.component.html',
  styleUrl: './notifications.component.scss',
})
export class NotificationsComponent implements OnInit {
  private notificationsService = inject(NotificationService);
  private modalService = inject(ModalService);
  private readListController!: ListController<NotificationParams>;
  private unreadListController!: ListController<NotificationParams>;

  readPaginatedNotifications: Paginated<Notification> | null = null;
  unreadPaginatedNotifications: Paginated<Notification> | null = null;
  unreadCount = 0;
  readCount = 0;

  readNotificationsParams: NotificationParams = createNotificationsParams();
  unreadNotificationsParams: NotificationParams = createNotificationsParams();
  notificationsState: LoadingState = 'idle';

  ngOnInit(): void {
    this.readListController = new ListController<NotificationParams>(
      () => this.readNotificationsParams,
      (params) => (this.readNotificationsParams = params),
      () => this.loadAllNotifications(),
    );
    this.unreadListController = new ListController<NotificationParams>(
      () => this.unreadNotificationsParams,
      (params) => (this.unreadNotificationsParams = params),
      () => this.loadAllNotifications(),
    );
    this.loadAllNotifications();
  }

  get notificationTabs(): FolderTab[] {
    return [
      {
        id: 'read',
        label: 'Leídas',
        icon: 'done_all',
        badge: this.readCount,
      },
      {
        id: 'unread',
        label: 'No leídas',
        icon: 'mark_email_unread',
        badge: this.unreadCount,
      },
    ];
  }

  activeNotificationTab = 'unread';

  loadAllNotifications() {
    this.notificationsState = 'loading';
    forkJoin({
      all: this.notificationsService.getAllNotifications(
        this.readNotificationsParams,
      ),
      unread: this.notificationsService.getUnreadNotifications(
        this.unreadNotificationsParams,
      ),
    }).subscribe({
      next: ({ all, unread }) => {
        this.notificationsState = 'success';
        this.readPaginatedNotifications = all.notifications;
        this.unreadCount = all.unread_count;
        this.readCount = all.read_count;
        this.unreadPaginatedNotifications = unread;
      },
      error: () => {
        this.notificationsState = 'error';
      },
    });
  }

  markAsRead(notification: Notification) {
    if (notification.read_at) {
      this.modalService.show({
        message: 'Esta notificación ya ha sido marcada como leída.',
        type: 'info',
        display: 'alert',
      });
      return;
    }
    this.notificationsService.markAsRead(notification.id).subscribe({
      next: () => {
        this.loadAllNotifications();
      },
    });
  }

  markAllAsRead() {
    this.notificationsService.markAllAsRead().subscribe({
      next: () => {
        this.loadAllNotifications();
      },
    });
  }

  deleteNotification(notification: Notification) {
    this.modalService.openConfirm({
      title: 'Eliminar notificación',
      message: `¿Deseas eliminar esta notificación? Esta acción no puede deshacerse.`,

      confirmLabel: 'Eliminar',
      confirmVariant: 'danger',

      onConfirm: () =>
        this.notificationsService.deleteNotification(notification.id),

      onSuccess: () => {
        this.loadAllNotifications();
      },
    });
  }

  onRefreshRead() {
    const updatedParams = QueryParamsHelper.refreshData(
      this.readNotificationsParams,
    );
    this.readListController.update(updatedParams);
  }

  onRefreshUnread() {
    const updatedParams = QueryParamsHelper.refreshData(
      this.unreadNotificationsParams,
    );
    this.unreadListController.update(updatedParams);
  }

  onRefresh() {
    this.onRefreshRead();
    this.onRefreshUnread();
  }

  onReadPageChange(newPage: number) {
    const updatedParams = QueryParamsHelper.changePage(
      this.readNotificationsParams,
      newPage,
    );
    this.readListController.update(updatedParams);
  }

  onReadPageSizeChange(newSize: number) {
    const updatedParams = QueryParamsHelper.changePageSize(
      this.readNotificationsParams,
      newSize,
    );
    this.readListController.update(updatedParams);
  }

  onUnreadPageChange(newPage: number) {
    const updatedParams = QueryParamsHelper.changePage(
      this.unreadNotificationsParams,
      newPage,
    );
    this.unreadListController.update(updatedParams);
  }

  onUnreadPageSizeChange(newSize: number) {
    const updatedParams = QueryParamsHelper.changePageSize(
      this.unreadNotificationsParams,
      newSize,
    );
    this.unreadListController.update(updatedParams);
  }
}
