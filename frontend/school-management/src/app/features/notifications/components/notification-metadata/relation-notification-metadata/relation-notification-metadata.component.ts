import { Component, Input } from '@angular/core';
import { RelationNotificationMetadata } from '../../../models/notification-metada.models';
import { MetadataCardComponent } from '../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { CommonModule } from '@angular/common';
import { MetadataRowComponent } from '../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';

@Component({
  selector: 'app-relation-notification-metadata',
  standalone: true,
  imports: [CommonModule, MetadataCardComponent, MetadataRowComponent],
  templateUrl: './relation-notification-metadata.component.html',
  styleUrl: './relation-notification-metadata.component.scss'
})
export class RelationNotificationMetadataComponent {
  @Input({ required: true })
  metadata!: RelationNotificationMetadata;

}
