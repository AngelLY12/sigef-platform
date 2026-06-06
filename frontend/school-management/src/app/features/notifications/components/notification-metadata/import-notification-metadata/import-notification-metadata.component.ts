import { Component, Input } from '@angular/core';
import { ImportNotificationMetadata } from '../../../models/notification-metada.models';
import { MetadataCardComponent } from '../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { CommonModule } from '@angular/common';
import { MetadataRowComponent } from '../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { MetadataBadgeComponent } from '../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';

@Component({
  selector: 'app-import-notification-metadata',
  standalone: true,
  imports: [CommonModule, MetadataCardComponent, MetadataRowComponent, MetadataBadgeComponent],
  templateUrl: './import-notification-metadata.component.html',
  styleUrl: './import-notification-metadata.component.scss',
})
export class ImportNotificationMetadataComponent {
  @Input({ required: true })
  metadata!: ImportNotificationMetadata;
}
