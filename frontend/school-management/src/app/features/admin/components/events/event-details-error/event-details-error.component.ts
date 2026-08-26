import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-event-details-error',
  imports: [],
  templateUrl: './event-details-error.component.html',
  styleUrl: './event-details-error.component.scss',
})
export class EventDetailsErrorComponent {
  @Input() message: string | null = null;
  @Input() title = 'Error de procesamiento';
  @Input() description = 'Se produjo un error al procesar este evento.';
}
