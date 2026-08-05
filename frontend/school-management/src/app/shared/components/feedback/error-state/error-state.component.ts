import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { ButtonComponent } from '../../ui/button/button.component';

@Component({
  selector: 'app-error-state',
  imports: [CommonModule,ButtonComponent],
  templateUrl: './error-state.component.html',
  styleUrl: './error-state.component.scss',
})
export class ErrorStateComponent {
  @Input() icon = 'error_outline';

  @Input()
  message = 'Error al cargar los datos. Intenta de nuevo.';

  @Input()
  showRetryButton = true;

  @Input()
  retryLabel = 'Reintentar';

  @Output()
  retry = new EventEmitter<void>();

  onRetry(): void {
    this.retry.emit();
  }
}
