import { Component, Input } from '@angular/core';
import { Address } from '../../../../../../core/models/domain/address.model';
import { InfoCardItemConfig } from '../../../../../../core/models/domain/cards/info-card-item-config.model';
import { ExpandableSectionComponent } from '../../../../../../shared/components/layout/expandable-section/expandable-section.component';
import { InfoCardItemComponent } from '../../../../../../shared/components/data-display/info-card-item/info-card-item.component';

@Component({
  selector: 'app-user-address',
  standalone: true,
  imports: [InfoCardItemComponent],
  templateUrl: './user-address.component.html',
  styleUrl: './user-address.component.scss',
})
export class UserAddressComponent {
  @Input() userAddress: Address | null = null;

  get addressInfoItems(): InfoCardItemConfig[] {
    if (!this.userAddress) {
      return [];
    }
    return [
      {
        icon: 'markunread_mailbox',
        label: 'Código Postal',
        value: this.userAddress?.cp || 'No disponible',
      },
      {
        icon: 'map',
        label: 'Estado',
        value: this.userAddress?.state || 'No disponible',
      },
      {
        icon: 'location_city',
        label: 'Municipio',
        value: this.userAddress?.city || 'No disponible',
      },
      {
        icon: 'apartment',
        label: 'Colonia',
        value: this.userAddress?.neighborhood || 'No disponible',
      },
      {
        icon: 'route',
        label: 'Calle',
        value: this.userAddress?.street || 'No disponible',
      },
      {
        icon: 'home',
        label: 'Número',
        value: this.userAddress?.number || 'No disponible',
      },
    ];
  }
}
