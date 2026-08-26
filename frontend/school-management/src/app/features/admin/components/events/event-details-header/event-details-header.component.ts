import { Component, EventEmitter, Input, Output } from '@angular/core';
import { EventDetailsHeaderData } from './event-details-header.model';
import { MetadataBadgeComponent } from '../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';

@Component({
  selector: 'app-event-details-header',
  standalone: true,
  imports: [MetadataBadgeComponent, ButtonComponent],
  templateUrl: './event-details-header.component.html',
  styleUrl: './event-details-header.component.scss'
})
export class EventDetailsHeaderComponent {
  @Input({ required: true }) event!: EventDetailsHeaderData;
  @Input() refreshing = false;

  @Output() refresh = new EventEmitter<void>();

}
