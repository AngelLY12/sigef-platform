import { CommonModule } from '@angular/common';
import { Component, Input, Type } from '@angular/core';
import { RelationNotificationMetadataComponent } from '../notification-metadata/relation-notification-metadata/relation-notification-metadata.component';
import { PromotionNotificationMetadataComponent } from '../notification-metadata/promotion-notification-metadata/promotion-notification-metadata.component';
import { PaymentConceptStatusNotificationMetadataComponent } from '../notification-metadata/payment-concept-status-notification-metadata/payment-concept-status-notification-metadata.component';
import { PaymentConceptNotificationMetadataComponent } from '../notification-metadata/payment-concept-notification-metadata/payment-concept-notification-metadata.component';
import { InvitationNotificationMetadataComponent } from '../notification-metadata/invitation-notification-metadata/invitation-notification-metadata.component';
import { ImportNotificationMetadataComponent } from '../notification-metadata/import-notification-metadata/import-notification-metadata.component';
import { Notification } from '../../../../core/models/domain/notification.model';
import { NotificationType } from '../../../../core/models/enums/notification-type.enum';

@Component({
  selector: 'app-notification-details',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './notification-details.component.html',
  styleUrl: './notification-details.component.scss',
})
export class NotificationDetailsComponent {
  @Input({ required: true })
  notification!: Notification;

  notificationMap: Record<NotificationType, Type<any>> = {
    [NotificationType.IMPORT_FINISHED]: ImportNotificationMetadataComponent,
    [NotificationType.IMPORT_ERROR]: ImportNotificationMetadataComponent,
    [NotificationType.INVITATION_ACCEPTED]: InvitationNotificationMetadataComponent,
    [NotificationType.INVITATION_FAILED]: InvitationNotificationMetadataComponent,
    [NotificationType.PAYMENT_CONCEPT_CHANGED]: PaymentConceptNotificationMetadataComponent,
    [NotificationType.PAYMENT_CONCEPT_STATUS_CHANGED]: PaymentConceptStatusNotificationMetadataComponent,
    [NotificationType.PROMOTION_COMPLETED]: PromotionNotificationMetadataComponent,
    [NotificationType.PROMOTION_FAILED]: PromotionNotificationMetadataComponent,
    [NotificationType.RELATION_DELETED]: RelationNotificationMetadataComponent,
  };

  get component() {
    return this.notificationMap[this.notification.data.type];
  }
}
