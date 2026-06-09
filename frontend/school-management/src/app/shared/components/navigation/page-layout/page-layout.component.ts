import {
  Component,
  EventEmitter,
  Input,
  Output,
  TemplateRef,
} from '@angular/core';
import { DashboardHeaderComponent } from '../dashboard-header/dashboard-header.component';
import { SpinnerComponent } from '../../ui/spinner/spinner.component';
import { CommonModule } from '@angular/common';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { ButtonComponent } from '../../ui/button/button.component';
import { ErrorStateComponent } from '../../feedback/error-state/error-state.component';

@Component({
  selector: 'app-page-layout',
  standalone: true,
  imports: [
    DashboardHeaderComponent,
    SpinnerComponent,
    CommonModule,
    ButtonComponent,
    ErrorStateComponent
  ],
  templateUrl: './page-layout.component.html',
  styleUrl: './page-layout.component.scss',
})
export class PageLayoutComponent {
  @Input() title: string = '';
  @Input() welcomeMessage: string = '';
  @Input() icon: string = '';
  @Input() iconSize: 'sm' | 'md' | 'lg' | 'xl' = 'lg';
  @Input() loadingMessage: string = 'Cargando contenido...';
  @Input() state: LoadingState = 'success';
  @Input() errorMessage: string =
    'Error al cargar los datos. Intenta de nuevo.';

  @Input() showAlertBadge = false;
  @Input() alertCount = 0;

  @Input() headerContent?: TemplateRef<any>;
  @Input() headerActions?: TemplateRef<any>;
  @Input() headerBottom?: TemplateRef<any>;
  @Input() content?: TemplateRef<any>;

  @Output() retry = new EventEmitter<void>();
  @Input() iconClickeable: boolean = false;
  @Output() iconAction = new EventEmitter<void>();

  currentDate = new Date();

  get isLoading(): boolean {
    return this.state === 'loading';
  }

  onIconAction() {
    this.iconAction.emit();
  }
}
