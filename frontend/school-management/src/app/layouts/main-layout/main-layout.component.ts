import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { SidebarComponent } from '../../shared/components/navigation/sidebar/sidebar.component';
import { NavigationService } from '../../core/services/navigation.service';
import { Role } from '../../core/models/enums/role.enum';
import { RoleSelectorComponent } from '../../shared/components/features/role-selector/role-selector.component';
import { MenuItem } from '../../core/models/menu-item.model';
import { NAVIGATION } from '../../core/navigation/navigation.config';
import { RouterModule } from '@angular/router';
import { AuthService } from '../../core/api/auth.api.service';

@Component({
  selector: 'app-main-layout',
  standalone:true,
  imports: [CommonModule,SidebarComponent, RoleSelectorComponent, RouterModule],
  templateUrl: './main-layout.component.html',
  styleUrl: './main-layout.component.scss'
})
export class MainLayoutComponent implements OnInit {
private authService = inject(AuthService);
  private navigationService = inject(NavigationService);

  userRoles: Role[] = [];
  userName = '';
  userAvatar = '';
  userRole!: Role;

  menuItems: MenuItem[] = [];
  collapsed = false;

  showRoleSelector = false;

  ngOnInit() {
    this.loadUserData();
  }

  private loadUserData() {
    const user = this.authService.currentUser();
    if (!user) return;

    this.userRoles = user.roles || [];
    this.userName = user.fullName || '';

    const savedRole = localStorage.getItem('preferredRole') as Role;

    if (savedRole && this.userRoles.includes(savedRole)) {
      this.setRole(savedRole);
      return;
    }

    if (this.userRoles.length === 1) {
      this.setRole(this.userRoles[0]);
      return;
    }

  }

  private setRole(role: Role) {
    this.userRole = role;
    this.buildMenu();
    this.setNotificationsBadge(
      this.authService.currentUser()?.hasUnreadNotifications ?? false
    );
  }

  private getMenuByRole(role: Role): MenuItem[] {

    const baseAdminMenu: MenuItem[] = [
      { label: 'Dashboard', icon: 'dashboard', route: NAVIGATION.admin.dashboard, key: 'dashboard' },
      { label: 'Usuarios', icon: 'people', route: NAVIGATION.admin.users, key: 'users' },
      { label: 'Importar datos', icon: 'bar_chart', route: NAVIGATION.admin.import, key: 'import' },
    ];

    const baseClientMenu: MenuItem[] = [
      { label: 'Dashboard', icon: 'dashboard', route: NAVIGATION.client.dashboard, key: 'dashboard' },
    ];

    const baseFinancialMenu: MenuItem[] = [
      { label: 'Dashboard', icon: 'dashboard', route: NAVIGATION.financial.dashboard, key: 'dashboard' },
      { label: 'Conceptos de pago', icon: 'receipt_long', route: NAVIGATION.financial.concepts, key: 'concepts'},
      { label: 'Adeudos', icon: 'request_quote ', route: NAVIGATION.financial.debts, key: 'debts' },
    ];

    const common: MenuItem[] = [
      { label: 'Notificaciones', icon: 'notifications', route: NAVIGATION.notifications.all, key: 'notifications' }
    ];

    switch (role) {
      case Role.ADMIN:
      case Role.SUPERVISOR:
        return [...baseAdminMenu, ...common];

      case Role.STUDENT:
      case Role.APPLICANT:
      case Role.PARENT:
        return [...baseClientMenu, ...common];

      case Role.FINANCIAL_STAFF:
        return [...baseFinancialMenu, ...common];

      default:
        return [];
    }
  }

  private buildMenu() {
    if (!this.userRole) return;
    this.menuItems = this.getMenuByRole(this.userRole);
  }

  onCollapsedChange(collapsed: boolean) {
    this.collapsed = collapsed;
  }

  openRoleSelector() {
    this.showRoleSelector = true;
  }

  onRoleSelected(role: Role) {
    localStorage.setItem('preferredRole', role);
    this.setRole(role);
    this.showRoleSelector = false;

    this.navigationService.navigateToRoleDashboard(role);
  }

  onSelectorClose() {
    this.showRoleSelector = false;
  }

  private setNotificationsBadge(hasUnread: boolean) {
    this.menuItems = this.menuItems.map(item => {
      if (item.key === 'notifications') {
        return {
          ...item,
          badge: hasUnread || undefined,
          badgeColor: 'error'
        };
      }
      return item;
    });
  }

  shouldShowRoleSelector(): boolean {
    return this.userRoles.length > 1;
  }

}
