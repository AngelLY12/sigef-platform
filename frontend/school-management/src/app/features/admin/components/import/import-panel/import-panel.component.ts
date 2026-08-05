import { Component, EventEmitter, Input, Output } from '@angular/core';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { FileInputComponent } from '../../../../../shared/components/form/controls/file-input/file-input.component';
import { InfoCardComponent } from '../../../../../shared/components/data-display/cards/info-card/info-card.component';

@Component({
  selector: 'app-import-panel',
  standalone: true,
  imports: [InfoCardComponent, ButtonComponent, FileInputComponent],
  templateUrl: './import-panel.component.html',
  styleUrl: './import-panel.component.scss',
})
export class ImportPanelComponent {
  @Input({ required: true }) mode!: 'users' | 'students';
  @Input() file?: File;
  @Input() loading = false;

  @Output() modeChange = new EventEmitter<void>();
  @Output() fileChange = new EventEmitter<File | undefined>();
  @Output() downloadTemplate = new EventEmitter<void>();
  @Output() import = new EventEmitter<void>();

  get title(): string {
    return this.mode === 'users' ? 'Importar usuarios' : 'Importar estudiantes';
  }

  get icon(): string {
    return this.mode === 'users' ? 'group' : 'school';
  }

  get description(): string {
    return this.mode === 'users'
      ? 'Sube un Excel con usuarios. Usa la plantilla para evitar errores.'
      : 'Sube un Excel con información académica por CURP.';
  }

  get inputLabel(): string {
    return this.mode === 'users'
      ? 'Arrastra un Excel de usuarios o haz clic'
      : 'Arrastra un Excel de estudiantes o haz clic';
  }
}
