import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { RecordListComponent } from '../../../../shared/components/data-display/record-list/record-list.component';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { NotificationService } from '../../../../core/api/notification.api.service';
import {
  createNotificationsParams,
  NotificationParams,
} from '../../../../core/models/domain/notification-params.model';
import { Notification } from '../../../../core/models/domain/notification.model';
import { ListController } from '../../../../core/utils/list-controller.utils';
import { PaginatorComponent } from '../../../../shared/components/data-display/paginator/paginator.component';
import { Paginated } from '../../../../core/utils/paginated-helper.utils';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { forkJoin } from 'rxjs';
import { ModalService } from '../../../../core/services/modal.service';

@Component({
  selector: 'app-notifications',
  standalone: true,
  imports: [
    CommonModule,
    RecordListComponent,
    PageLayoutComponent,
    PaginatorComponent,
    ButtonComponent,
  ],
  templateUrl: './notifications.component.html',
  styleUrl: './notifications.component.scss',
})
export class NotificationsComponent implements OnInit {
  private notificationsService = inject(NotificationService);
  private modalService = inject(ModalService);
  private listController!: ListController<NotificationParams>;

  paginatedNotifications: Paginated<Notification> | null = null;
  unreadNotifications: Notification[] = [];
  unreadCount = 0;
  readCount = 0;

  notificationsParams: NotificationParams = createNotificationsParams();
  notificationsState: LoadingState = 'idle';

  onlyUnread = false;

  ngOnInit(): void {
    this.listController = new ListController<NotificationParams>(
      () => this.notificationsParams,
      (params) => (this.notificationsParams = params),
      () => this.loadAllNotifications(),
    );
    this.loadAllNotifications();
  }

  loadAllNotifications() {
    this.notificationsState = 'loading';
    forkJoin({
      all: this.notificationsService.getAllNotifications(
        this.notificationsParams,
      ),
      unread: this.notificationsService.getUnreadNotifications(),
    }).subscribe({
      next: ({ all, unread }) => {
        this.notificationsState = 'success';
        this.paginatedNotifications = all.notifications;
        this.unreadCount = all.unreadCount;
        this.readCount = all.readCount;
        this.unreadNotifications = unread.notifications;
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

      onConfirm: () => this.notificationsService.deleteNotification(notification.id),

      onSuccess: () => {
        this.loadAllNotifications();
      },
    });
  }

  onRefresh() {
    this.loadAllNotifications();
  }

  onPageChange(newPage: number) {
    const updatedParams = QueryParamsHelper.changePage(
      this.notificationsParams,
      newPage,
    );
    this.listController.update(updatedParams);
  }

  onPageSizeChange(newSize: number) {
    const updatedParams = QueryParamsHelper.changePageSize(
      this.notificationsParams,
      newSize,
    );
    this.listController.update(updatedParams);
  }

  getNotificationIcon(type: string): string {
    switch (type) {
      case 'warning':
        return 'warning';
      case 'error':
        return 'error';
      case 'success':
        return 'check_circle';
      default:
        return 'notifications';
    }
  }
}
