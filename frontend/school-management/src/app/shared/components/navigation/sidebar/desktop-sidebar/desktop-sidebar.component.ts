import { CommonModule } from '@angular/common';
import {
  Component,
  EventEmitter,
  inject,
  Input,
  OnChanges,
  Output,
} from '@angular/core';
import { ButtonComponent } from '../../../ui/button/button.component';
import { Router, RouterModule } from '@angular/router';
import { AvatarComponent } from '../../../ui/avatar/avatar.component';
import { SideBarItem } from '../sidebar-item.model';
import { Role } from '../../../../../core/models/enums/role.enum';
import { NAVIGATION } from '../../../../../core/navigation/navigation.config';
import { SidebarMenuItemComponent } from './sidebar-menu-item/sidebar-menu-item.component';
import { SidebarMenuGroupComponent } from './sidebar-menu-group/sidebar-menu-group.component';

@Component({
  selector: 'app-desktop-sidebar',
  standalone: true,
  imports: [
    CommonModule,
    RouterModule,
    ButtonComponent,
    AvatarComponent,
    SidebarMenuItemComponent,
    SidebarMenuGroupComponent,
  ],
  templateUrl: './desktop-sidebar.component.html',
  styleUrl: './desktop-sidebar.component.scss',
})
export class DesktopSidebarComponent {
  private router = inject(Router);

  @Input() sideBarItems: SideBarItem[] = [];
  @Input() collapsed = false;
  @Input() logoText = 'SIGEF';
  @Input() logoIcon = 'payments';
  @Input() role!: Role;
  @Input() userName = '';
  @Input() userAvatar = '';
  @Input() showUserInfo = true;
  @Input() showRoleSelector = false;
  @Input() showChildrenSelector = false;
  @Output() collapsedChange = new EventEmitter<boolean>();
  @Output() changeRole = new EventEmitter<void>();
  @Output() selectChild = new EventEmitter<void>();

  toggleSidebar(): void {
    this.collapsed = !this.collapsed;

    this.collapsedChange.emit(this.collapsed);
  }

  onProfileClick(): void {
    this.router.navigate([NAVIGATION.profile.view]);
  }

  onChangeRole(): void {
    if (this.showRoleSelector) {
      this.changeRole.emit();
    }
  }

  onSelectChild(): void {
    if (this.showChildrenSelector) {
      this.selectChild.emit();
    }
  }
}
