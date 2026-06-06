import { Component, Input } from '@angular/core';
import { InvitationNotificationMetadata } from '../../../models/notification-metada.models';
import { MetadataCardComponent } from '../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { CommonModule } from '@angular/common';
import { MetadataRowComponent } from '../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';

@Component({
  selector: 'app-invitation-notification-metadata',
  standalone: true,
  imports: [CommonModule, MetadataCardComponent, MetadataRowComponent],
  templateUrl: './invitation-notification-metadata.component.html',
  styleUrl: './invitation-notification-metadata.component.scss'
})
export class InvitationNotificationMetadataComponent {
  @Input({ required: true })
  metadata!: InvitationNotificationMetadata;

}
