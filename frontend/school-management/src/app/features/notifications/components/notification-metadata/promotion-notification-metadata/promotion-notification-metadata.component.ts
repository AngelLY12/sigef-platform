import { Component, Input } from '@angular/core';
import { PromotionNotificationMetadata } from '../../../models/notification-metada.models';
import { MetadataCardComponent } from '../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { CommonModule } from '@angular/common';
import { MetadataBadgeComponent } from '../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { MetadataRowComponent } from '../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';

@Component({
  selector: 'app-promotion-notification-metadata',
  standalone: true,
  imports: [CommonModule, MetadataCardComponent, MetadataRowComponent, MetadataBadgeComponent],
  templateUrl: './promotion-notification-metadata.component.html',
  styleUrl: './promotion-notification-metadata.component.scss'
})
export class PromotionNotificationMetadataComponent {
  @Input({ required: true })
  metadata!: PromotionNotificationMetadata;

}
