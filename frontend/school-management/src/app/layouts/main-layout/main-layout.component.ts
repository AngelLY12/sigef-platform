import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { SidebarComponent } from '../../shared/components/navigation/sidebar/sidebar.component';
import { NavigationService } from '../../core/services/navigation.service';
import { Role } from '../../core/models/enums/role.enum';
import { RoleSelectorComponent } from '../../shared/components/domain/role-selector/role-selector.component';
import { RouterModule } from '@angular/router';
import { AuthService } from '../../core/api/auth/auth.api.service';
import { SideBarItem } from '../../shared/components/navigation/sidebar/sidebar-item.model';
import {
  ADMIN_MENU,
  CLIENT_MENU,
  COMMON_MENU,
  FINANCIAL_MENU,
  STUDENT_MENU,
} from '../config/main-layout.config';
import { ChildrenData } from '../../features/client/models/parents/children.model';
import { ChildrenSelectorComponent } from '../../shared/components/domain/children-selector/children-selector.component';
import { ChildSelectionService } from '../../core/services/child-selection.service';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [
    CommonModule,
    SidebarComponent,
    RoleSelectorComponent,
    RouterModule,
    ChildrenSelectorComponent,
  ],
  templateUrl: './main-layout.component.html',
  styleUrl: './main-layout.component.scss',
})
export class MainLayoutComponent implements OnInit {
  private authService = inject(AuthService);
  private navigationService = inject(NavigationService);
  private studentContext = inject(ChildSelectionService);

  userRoles: Role[] = [];
  userName = '';
  userAvatar = '';
  userRole!: Role;

  sideBarItems: SideBarItem[] = [];
  collapsed = false;

  showRoleSelector = false;
  isChildrenSelectorOpen = false;
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
      this.authService.currentUser()?.hasUnreadNotifications ?? false,
    );
  }

  private getMenuByRole(role: Role): SideBarItem[] {
    const baseAdminMenu: SideBarItem[] = ADMIN_MENU;

    const baseClientMenu: SideBarItem[] = CLIENT_MENU;

    const baseFinancialMenu: SideBarItem[] = FINANCIAL_MENU;

    const common: SideBarItem[] = COMMON_MENU;

    const studentMenu: SideBarItem[] = STUDENT_MENU;

    switch (role) {
      case Role.ADMIN:
      case Role.SUPERVISOR:
        return [...baseAdminMenu, ...common];

      case Role.STUDENT:
        return [...baseClientMenu, ...studentMenu, ...common];

      case Role.APPLICANT:
        return [...baseClientMenu, ...common];

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
    this.sideBarItems = this.getMenuByRole(this.userRole);
  }

  onCollapsedChange(collapsed: boolean) {
    this.collapsed = collapsed;
  }

  openRoleSelector() {
    this.showRoleSelector = true;
  }

  openChildrenSelector() {
    this.isChildrenSelectorOpen = true;
  }

  onChildSelected(child: ChildrenData) {
    this.studentContext.setChild(child);
    this.isChildrenSelectorOpen = false;
    this.navigationService.navigateToRoleDashboard(Role.PARENT);
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

  onChildrenSelectorClose() {
    this.isChildrenSelectorOpen = false;
  }

  private setNotificationsBadge(hasUnread: boolean) {
    this.sideBarItems = this.sideBarItems.map((item) => {
      if (item.key === 'notifications') {
        return {
          ...item,
          badge: hasUnread || undefined,
          badgeColor: 'error',
        };
      }
      return item;
    });
  }

  shouldShowRoleSelector(): boolean {
    return this.userRoles.length > 1;
  }

  canSelectChildren(): boolean {
    return this.userRole === Role.PARENT;
  }
}
