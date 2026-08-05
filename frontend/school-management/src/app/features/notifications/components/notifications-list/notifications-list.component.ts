import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { PaginatorComponent } from '../../../../shared/components/data-controls/paginator/paginator.component';
import { RecordListComponent } from '../../../../shared/components/data-display/lists/record-list/record-list.component';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { Paginated } from '../../../../core/utils/paginated-helper.utils';
import { Notification } from '../../models/notification.model';
import { NotificationSeverity } from '../../../../core/models/enums/notification-severity.enum';
import { NotificationType } from '../../../../core/models/enums/notification-type.enum';
import { NotificationDetailsComponent } from '../notification-details/notification-details.component';
import { ExpandableSectionComponent } from '../../../../shared/components/layout/expandable-section/expandable-section.component';

@Component({
  selector: 'app-notifications-list',
  standalone: true,
  imports: [
    CommonModule,
    PaginatorComponent,
    RecordListComponent,
    ButtonComponent,
    ExpandableSectionComponent,
    NotificationDetailsComponent
  ],
  templateUrl: './notifications-list.component.html',
  styleUrl: './notifications-list.component.scss',
})
export class NotificationsListComponent {
  @Input({ required: true })
  paginatedNotifications!: Paginated<Notification>;
  @Output() markAsRead = new EventEmitter<Notification>();
  @Output() deleteNotification = new EventEmitter<Notification>();

  @Output() pageChange = new EventEmitter<number>();
  @Output() pageSizeChange = new EventEmitter<number>();

  private readonly notificationIcons: Record<NotificationType, string> = {
    [NotificationType.PAYMENT_CONCEPT_CHANGED]: 'payments',
    [NotificationType.PAYMENT_CONCEPT_STATUS_CHANGED]: 'price_change',
    [NotificationType.IMPORT_FINISHED]: 'file_download_done',
    [NotificationType.IMPORT_ERROR]: 'file_download_off',
    [NotificationType.PROMOTION_COMPLETED]: 'school',
    [NotificationType.PROMOTION_FAILED]: 'school',
    [NotificationType.INVITATION_ACCEPTED]: 'person_add',
    [NotificationType.INVITATION_FAILED]: 'person_off',
    [NotificationType.RELATION_DELETED]: 'link_off',
  };

  getNotificationIcon(notification: Notification): string {
    return this.notificationIcons[notification.data.type] ?? 'notifications';
  }

  private readonly notificationClasses: Record<NotificationSeverity, string> = {
    [NotificationSeverity.INFO]: 'notification-info',
    [NotificationSeverity.SUCCESS]: 'notification-success',
    [NotificationSeverity.WARNING]: 'notification-warning',
    [NotificationSeverity.ERROR]: 'notification-error',
  };

  getNotificationClass(notification: Notification): string {
    return (
      this.notificationClasses[notification.data.severity] ??
      'notification-info'
    );
  }
}
