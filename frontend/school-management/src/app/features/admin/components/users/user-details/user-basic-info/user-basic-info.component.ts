import { Component, Input } from '@angular/core';
import { InfoCardItemConfig } from '../../../../../../shared/components/data-display/cards/info-card-item/info-card-item-config.model';
import { UserBasicInfo } from '../../../../models/response/user-details.model';
import { InfoCardItemComponent } from '../../../../../../shared/components/data-display/cards/info-card-item/info-card-item.component';

@Component({
  selector: 'app-user-basic-info',
  standalone: true,
  imports:[InfoCardItemComponent],
  templateUrl: './user-basic-info.component.html',
  styleUrl: './user-basic-info.component.scss',
})
export class UserBasicInfoComponent {
  @Input({ required: true }) basicInfo!: UserBasicInfo;
  get basicInfoItems(): InfoCardItemConfig[] {
    return [
      {
        icon: 'phone',
        label: 'Número de teléfono',
        value: this.basicInfo.phone_number || 'No disponible',
      },
      {
        icon: 'cake',
        label: 'Fecha de nacimiento',
        value: this.basicInfo.birthdate || 'No disponible',
      },
      {
        icon: 'calendar_today',
        label: 'Edad',
        value: this.basicInfo.age.toString() || 'No disponible',
      },
    ];
  }
}
