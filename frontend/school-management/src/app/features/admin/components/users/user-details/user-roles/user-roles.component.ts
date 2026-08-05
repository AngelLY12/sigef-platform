import { Component, EventEmitter, Input, Output } from '@angular/core';
import { Role } from '../../../../../../core/models/enums/role.enum';
import { RolesHelper } from '../../../../../../core/utils/roles-helper';
import { InfoCardItemConfig } from '../../../../../../shared/components/data-display/cards/info-card-item/info-card-item-config.model';
import { InfoCardItemComponent } from '../../../../../../shared/components/data-display/cards/info-card-item/info-card-item.component';
import { ButtonComponent } from '../../../../../../shared/components/ui/button/button.component';

@Component({
  selector: 'app-user-roles',
  standalone: true,
  imports: [InfoCardItemComponent, ButtonComponent],
  templateUrl: './user-roles.component.html',
  styleUrl: './user-roles.component.scss',
})
export class UserRolesComponent {
  @Input({ required: true }) userRoles!: Role[];
  @Output() edit = new EventEmitter<void>();

  get rolesItems(): InfoCardItemConfig[] {
    return (
      this.userRoles.map((role) => ({
        icon: 'badge',
        label: 'Role',
        value: RolesHelper.getLabel(role),
      })) || []
    );
  }
}
