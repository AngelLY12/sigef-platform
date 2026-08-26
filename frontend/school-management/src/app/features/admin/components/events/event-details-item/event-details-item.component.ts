import { Component, Input } from '@angular/core';
import { EventDetailsItemVariant } from './event-details-item.variant.type';

@Component({
  selector: 'app-event-details-item',
  imports: [],
  templateUrl: './event-details-item.component.html',
  styleUrl: './event-details-item.component.scss'
})
export class EventDetailsItemComponent {
  @Input({ required: true }) label!: string;
  @Input({ required: true }) value!: any;
  @Input() variant: EventDetailsItemVariant = 'default';
  @Input() divider = false;
}
