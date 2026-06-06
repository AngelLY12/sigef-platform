import { Component, Input } from '@angular/core';
import { PaymentConceptChangedMetadata } from '../../../models/notification-metada.models';
import { MetadataCardComponent } from '../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { CommonModule } from '@angular/common';
import { MetadataBadgeComponent } from '../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { MetadataRowComponent } from '../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import {
  NOTIFICATION_CONCEPT_ACTION_LABELS,
  NOTIFICATION_PRIORITY_LABELS,
} from '../../../../../core/constants/notifications.constants';
import { NotificationConceptAction } from '../../../../../core/models/enums/notification-concept-action.enum';
import { NotificationConceptPriority } from '../../../../../core/models/enums/notification-concept-priority.enum';
import { statusType } from '../../../../../core/models/types/status-type.type';
import { MetadataListComponent } from '../../../../../shared/components/data-display/metadata/metadata-list/metadata-list.component';

@Component({
  selector: 'app-payment-concept-notification-metadata',
  standalone: true,
  imports: [
    CommonModule,
    MetadataCardComponent,
    MetadataRowComponent,
    MetadataBadgeComponent,
    MetadataListComponent
  ],
  templateUrl: './payment-concept-notification-metadata.component.html',
  styleUrl: './payment-concept-notification-metadata.component.scss',
})
export class PaymentConceptNotificationMetadataComponent {
  @Input({ required: true })
  metadata!: PaymentConceptChangedMetadata;

  getActionLabel(action: NotificationConceptAction): string {
    return NOTIFICATION_CONCEPT_ACTION_LABELS[action];
  }

  getPriorityLabel(priority: NotificationConceptPriority): string {
    return NOTIFICATION_PRIORITY_LABELS[priority] || priority;
  }

  getPriorityType(priority: NotificationConceptPriority): statusType {
    switch (priority) {
      case NotificationConceptPriority.HIGH:
        return 'error';
      case NotificationConceptPriority.MEDIUM:
        return 'warning';
      case NotificationConceptPriority.LOW:
        return 'info';
      default:
        return 'info';
    }
  }

}
