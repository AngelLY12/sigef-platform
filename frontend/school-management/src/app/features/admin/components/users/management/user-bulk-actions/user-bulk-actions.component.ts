import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CheckboxComponent } from '../../../../../../shared/components/form/controls/checkbox/checkbox.component';
import { MenuComponent } from '../../../../../../shared/components/navigation/menu/menu.component';
import { MenuItemComponent } from '../../../../../../shared/components/navigation/menu-item/menu-item.component';
import { ButtonComponent } from '../../../../../../shared/components/ui/button/button.component';
import { FormsModule } from '@angular/forms';
import { UserListItem } from '../../../../models/response/user-list-item.model';

@Component({
  selector: 'app-user-bulk-actions',
  standalone: true,
  imports: [
    CheckboxComponent,
    MenuComponent,
    MenuItemComponent,
    ButtonComponent,
    FormsModule,
  ],
  templateUrl: './user-bulk-actions.component.html',
  styleUrl: './user-bulk-actions.component.scss'
})
export class UserBulkActionsComponent {
  @Input() selected: UserListItem[] = [];
  @Input() totalItems = 0;

  @Output() toggleAll = new EventEmitter<boolean>();

  @Output() delete = new EventEmitter<void>();
  @Output() activate = new EventEmitter<void>();
  @Output() disable = new EventEmitter<void>();
  @Output() temporaryDisable = new EventEmitter<void>();
  @Output() updateRoles = new EventEmitter<void>();
  @Output() updatePermissions = new EventEmitter<void>();
  @Output() promoteStudents = new EventEmitter<void>();

  get allSelected(): boolean {
    return (
      this.totalItems > 0 &&
      this.selected.length === this.totalItems
    );
  }

}
