import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-event-details-section',
  imports: [],
  templateUrl: './event-details-section.component.html',
  styleUrl: './event-details-section.component.scss'
})
export class EventDetailsSectionComponent {
  @Input({ required: true }) title!: string;
  @Input() description: string | null = null;
}
