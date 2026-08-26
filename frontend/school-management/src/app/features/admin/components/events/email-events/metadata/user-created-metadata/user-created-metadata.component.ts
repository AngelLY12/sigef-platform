import { Component, Input } from '@angular/core';
import { MetadataCardComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { MetadataRowComponent } from '../../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { UserCreatedMetadataResponse } from '../../../../../models/response/events/email/metadata/user-created-metadata-response.model';

@Component({
  selector: 'app-user-created-metadata',
  standalone: true,
  imports: [MetadataCardComponent, MetadataRowComponent],
  templateUrl: './user-created-metadata.component.html',
  styleUrl: './user-created-metadata.component.scss'
})
export class UserCreatedMetadataComponent {
  @Input({ required: true}) metadata!: UserCreatedMetadataResponse;
}
