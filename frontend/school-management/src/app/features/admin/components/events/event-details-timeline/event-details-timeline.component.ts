import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { MetadataBadgeComponent } from '../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { EventTimelineItem } from './event-details-timeline.model';

@Component({
  selector: 'app-event-details-timeline',
  imports: [CommonModule, MetadataBadgeComponent],
  templateUrl: './event-details-timeline.component.html',
  styleUrl: './event-details-timeline.component.scss'
})
export class EventDetailsTimelineComponent {
  @Input({ required: true }) items!: EventTimelineItem[];
}
