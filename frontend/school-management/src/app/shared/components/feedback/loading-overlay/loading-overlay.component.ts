import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { SpinnerComponent } from '../../ui/spinner/spinner.component';

@Component({
  selector: 'app-loading-overlay',
  imports: [CommonModule, SpinnerComponent],
  templateUrl: './loading-overlay.component.html',
  styleUrl: './loading-overlay.component.scss'
})
export class LoadingOverlayComponent {
  @Input() visible = false;
  @Input() message = 'Cargando...';

}
