import { Component, Input } from '@angular/core';
import { PaymentConceptStatusChangedMetadata } from '../../../models/notification-metada.models';
import { CommonModule } from '@angular/common';
import { MetadataCardComponent } from '../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataBadgeComponent } from '../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { MetadataRowComponent } from '../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { NOTIFICATION_PRIORITY_LABELS } from '../../../../../core/constants/notifications.constants';
import { NotificationConceptPriority } from '../../../../../core/models/enums/notification-concept-priority.enum';
import { statusType } from '../../../../../core/models/types/status-type.type';
import { Status } from '../../../../../core/models/enums/status.enum';
import { CurrencyMXNPipe } from '../../../../../shared/pipes/currency-mxn.pipe';
import { PaymentConceptStatus } from '../../../../../core/models/enums/payment-concepts-status.enum';
import { PAYMENT_CONCEPT_STATUS_LABELS } from '../../../../../core/constants/status.constats';

@Component({
  selector: 'app-payment-concept-status-notification-metadata',
  standalone: true,
  imports: [
    CommonModule,
    MetadataCardComponent,
    MetadataRowComponent,
    MetadataBadgeComponent,
    CurrencyMXNPipe
  ],
  templateUrl: './payment-concept-status-notification-metadata.component.html',
  styleUrl: './payment-concept-status-notification-metadata.component.scss',
})
export class PaymentConceptStatusNotificationMetadataComponent {
  @Input({ required: true })
  metadata!: PaymentConceptStatusChangedMetadata;

  getStatusLabel(status: string | undefined): string {
    return PAYMENT_CONCEPT_STATUS_LABELS[status as PaymentConceptStatus] || status || 'Desconocido';
  }

  getStatusTransitionLabel(transition: string | undefined): string {
    const [from, to] = transition?.split('_to_') || ['', ''];
    return `${PAYMENT_CONCEPT_STATUS_LABELS[from as PaymentConceptStatus]} → ${PAYMENT_CONCEPT_STATUS_LABELS[to as PaymentConceptStatus]}`;
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
