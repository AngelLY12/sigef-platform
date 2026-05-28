import { MenuItem } from '../../../../core/models/menu-item.model';
import { CommonModule } from '@angular/common';
import {
  Component,
  EventEmitter,
  HostListener,
  inject,
  Input,
  OnInit,
  Output,
} from '@angular/core';
import { Router, RouterModule } from '@angular/router';
import { ButtonComponent } from '../../ui/button/button.component';
import { AvatarComponent } from '../../ui/avatar/avatar.component';
import { NAVIGATION } from '../../../../core/navigation/navigation.config';
import { Role } from '../../../../core/models/enums/role.enum';

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [CommonModule, RouterModule, ButtonComponent, AvatarComponent],
  templateUrl: './sidebar.component.html',
  styleUrl: './sidebar.component.scss',
})
export class SidebarComponent implements OnInit {
  private router = inject(Router);
  @Input() menuItems: MenuItem[] = [];
  @Input() collapsed: boolean = false;
  @Input() logoText: string = 'SIGEF';
  @Input() logoIcon: string = 'payments';

  @Input() role!: Role;
  @Input() userName: string = '';
  @Input() userAvatar: string = '';
  @Input() showUserInfo: boolean = true;
  @Input() showRoleSelector: boolean = false;

  @Output() collapsedChange = new EventEmitter<boolean>();
  @Output() changeRole = new EventEmitter<void>();

  activeItem: string = '';
  expandedMenus: Set<string> = new Set();
  mobileOpen = false;

  ngOnInit() {
    this.activeItem = this.router.url;
    this.handleResponsive();
  }

  @HostListener('window:resize')
  onResize() {
    this.handleResponsive();
  }

  handleResponsive() {
    const isMobile = window.innerWidth <= 768;

    if (isMobile) {
      this.mobileOpen = false;

      if (this.collapsed) {
        this.collapsed = false;
        this.collapsedChange.emit(this.collapsed);
      }
    }
  }

  toggleSidebar() {
    const isMobile = window.innerWidth <= 768;

    if (isMobile) {
      this.mobileOpen = !this.mobileOpen;
    } else {
      this.collapsed = !this.collapsed;
      this.collapsedChange.emit(this.collapsed);
    }
  }

  closeMobileSidebar() {
    if (window.innerWidth <= 768) {
      this.mobileOpen = false;
    }
  }

  toggleSubMenu(menuLabel: string) {
    if (this.expandedMenus.has(menuLabel)) {
      this.expandedMenus.delete(menuLabel);
    } else {
      this.expandedMenus.add(menuLabel);
    }
  }

  isMenuExpanded(menuLabel: string): boolean {
    return this.expandedMenus.has(menuLabel);
  }

  isActive(route: string, exact: boolean = false): boolean {
    if (exact) {
      return this.router.url === route;
    }
    return this.router.url.startsWith(route);
  }

  getBadgeClass(color: string = 'primary'): string {
    return `badge-${color}`;
  }

  onProfileClick() {
    this.router.navigate([NAVIGATION.profile.view]);
  }
  onChangeRole() {
    if (this.showRoleSelector) {
      this.changeRole.emit();
    }
  }
}
