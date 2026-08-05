import { Component, EventEmitter, Input, Output } from '@angular/core';
import { InfoCardItemConfig } from '../../../../../../shared/components/data-display/cards/info-card-item/info-card-item-config.model';
import { InfoCardItemComponent } from '../../../../../../shared/components/data-display/cards/info-card-item/info-card-item.component';
import { UserStudentDetail } from '../../../../models/response/user-details.model';
import { ButtonComponent } from '../../../../../../shared/components/ui/button/button.component';

@Component({
  selector: 'app-user-academic-info',
  standalone: true,
  imports: [InfoCardItemComponent, ButtonComponent],
  templateUrl: './user-academic-info.component.html',
  styleUrl: './user-academic-info.component.scss',
})
export class UserAcademicInfoComponent {
  @Input() userStudentDetail: UserStudentDetail | null = null;
  @Input() loading = false;

  @Output() edit = new EventEmitter<void>();
  get studentDetailItems(): InfoCardItemConfig[] {
    if (!this.userStudentDetail) return [];
    const detail = this.userStudentDetail;
    return [
      {
        icon: 'menu_book',
        label: 'Carrera',
        value: detail.careerName || 'No disponible',
      },
      {
        icon: 'badge',
        label: 'Número de control',
        value: detail.nControl || 'No disponible',
      },
      {
        icon: 'school',
        label: 'Semestre',
        value: detail.semestre.toString() || 'No disponible',
      },
      {
        icon: 'group',
        label: 'Grupo',
        value: detail.group || 'No disponible',
      },
      {
        icon: 'work',
        label: 'Taller',
        value: detail.workshop || 'No disponible',
      },
    ];
  }
}
