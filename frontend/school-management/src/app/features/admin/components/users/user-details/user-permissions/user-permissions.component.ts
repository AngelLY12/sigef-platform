import { Permission } from './../../../../../../core/models/types/permissions.type';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { InfoCardItemComponent } from '../../../../../../shared/components/data-display/cards/info-card-item/info-card-item.component';
import { ExpandableSectionComponent } from '../../../../../../shared/components/layout/expandable-section/expandable-section.component';
import { PermissionsHelper } from '../../../../../../core/utils/permissions-helper.utils';
import { ButtonComponent } from '../../../../../../shared/components/ui/button/button.component';

@Component({
  selector: 'app-user-permissions',
  standalone: true,
  imports: [InfoCardItemComponent, ButtonComponent],
  templateUrl: './user-permissions.component.html',
  styleUrl: './user-permissions.component.scss',
})
export class UserPermissionsComponent {
  @Input() userPermissions: Permission[] = [];
  @Input() loading = false;

  @Output() edit = new EventEmitter<void>();
  get permissionsItems() {
    return (
      this.userPermissions.map((permission) => ({
        icon: 'verified',
        label: PermissionsHelper.getGroup(permission),
        value: PermissionsHelper.getLabel(permission),
      })) || []
    );
  }
}
