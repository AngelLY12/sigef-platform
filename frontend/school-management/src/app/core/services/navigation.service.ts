import { inject, Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { Role } from '../models/enums/role.enum';
import { NAVIGATION } from '../navigation/navigation.config';

@Injectable({ providedIn: 'root' })
export class NavigationService {
  private router = inject(Router);
  private pendingRoles: Role[] = [];
  redirectByRole(roles: string[]): void {
    const validRoles = roles.filter((r) => r !== Role.UNVERIFIED) as Role[];

    if (validRoles.length > 1) {
      this.pendingRoles = validRoles;
      return;
    }

    if (validRoles.length === 1) {
      this.navigateToRoleDashboard(validRoles[0]);
      return;
    }

    this.router.navigate([NAVIGATION.common.unverified]);
  }

  navigateToRoleDashboard(role: Role): void {
    const roleRoutes: Record<Role, string> = {
      [Role.ADMIN]: NAVIGATION.admin.dashboard,
      [Role.APPLICANT]: NAVIGATION.client.dashboard,
      [Role.FINANCIAL_STAFF]: NAVIGATION.financial.dashboard,
      [Role.STUDENT]: NAVIGATION.client.dashboard,
      [Role.SUPERVISOR]: NAVIGATION.admin.dashboard,
      [Role.PARENT]: NAVIGATION.client.dashboard,
      [Role.UNVERIFIED]: NAVIGATION.common.unverified,
    };

    this.router.navigate([roleRoutes[role]]);
  }

  getDashboardRoute(role: string): string {
    const routes: Record<Role, string> = {
      [Role.ADMIN]: NAVIGATION.admin.dashboard,
      [Role.APPLICANT]: NAVIGATION.client.dashboard,
      [Role.FINANCIAL_STAFF]: NAVIGATION.financial.dashboard,
      [Role.STUDENT]: NAVIGATION.client.dashboard,
      [Role.SUPERVISOR]: NAVIGATION.admin.dashboard,
      [Role.PARENT]: NAVIGATION.client.dashboard,
      [Role.UNVERIFIED]: NAVIGATION.common.unverified,
    };

    const roleEnum = role as Role;
    return routes[roleEnum] || NAVIGATION.common.unverified;
  }

  saveRolePreference(role: Role): void {
    localStorage.setItem('preferredRole', role);
    this.navigateToRoleDashboard(role);
  }

  cancelRoleSelection(): void {
    this.pendingRoles = [];
    this.router.navigate([NAVIGATION.auth.login]);
  }
  goToSelector(): void {
    this.router.navigate([NAVIGATION.common.selector]);
  }
}
