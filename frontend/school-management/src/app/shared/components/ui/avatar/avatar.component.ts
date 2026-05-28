import { Role } from './../../../../core/models/enums/role.enum';
import { CommonModule } from '@angular/common';
import { Component, EventEmitter, inject, Input, Output } from '@angular/core';
import { ThemeButtonComponent } from '../theme-button/theme-button.component';
import { SpinnerComponent } from '../spinner/spinner.component';
import { LogoutService } from '../../../../core/services/logout.service';
import { MenuComponent } from '../../navigation/menu/menu.component';
import { MenuItemComponent } from '../../navigation/menu-item/menu-item.component';
import { RolesHelper } from '../../../../core/utils/roles-helper';
import { DropdownComponent } from '../../layout/dropdown/dropdown.component';

@Component({
  selector: 'app-avatar',
  imports: [
    CommonModule,
    ThemeButtonComponent,
    SpinnerComponent,
    DropdownComponent,
    MenuItemComponent,
  ],
  templateUrl: './avatar.component.html',
  styleUrl: './avatar.component.scss',
})
export class AvatarComponent {
  private logoutService = inject(LogoutService);
  isLoading = false;
  @Input() role!: Role;
  @Input() userName: string = '';
  @Input() userAvatar: string = '';
  @Input() collapsed: boolean = false;
  @Input() showUserInfo: boolean = true;
  @Input() showRoleSelector: boolean = false;

  @Output() profileClick = new EventEmitter<void>();
  @Output() changeRole = new EventEmitter<void>();

  onProfileClick(menu: DropdownComponent) {
    menu.closeDropdown();
    this.profileClick.emit();
  }
  onChangeRole(menu: DropdownComponent) {
    menu.closeDropdown();
    this.changeRole.emit();
  }

  logout(): void {
    this.isLoading = true;
    this.logoutService.logout().subscribe({
      complete: () => (this.isLoading = false),
    });
  }
  onDisplayRole(): string {
    return RolesHelper.getLabel(this.role);
  }
}
