import { Component, inject } from '@angular/core';
import { RoleSelectorComponent } from '../../../../shared/components/features/role-selector/role-selector.component';
import { NavigationService } from '../../../../core/services/navigation.service';
import { AuthService } from '../../../../core/api/auth.api.service';
import { Role } from '../../../../core/models/enums/role.enum';
import { PublicLayoutComponent } from '../../../../layouts/public-layout/public-layout.component';
import { LogoutService } from '../../../../core/services/logout.service';
import { LoadingState } from '../../../../core/models/types/loading-state.type';

@Component({
  selector: 'app-role-selector-page',
  imports: [RoleSelectorComponent, PublicLayoutComponent],
  templateUrl: './role-selector-page.component.html',
  styleUrl: './role-selector-page.component.scss',
})
export class RoleSelectorPageComponent {
  private navigationService = inject(NavigationService);
  private authService = inject(AuthService);
  private logoutService = inject(LogoutService);


  userRoles: Role[] = [];
  userName: string = '';
  showRoleSelector = false;
  logoutState: LoadingState = 'idle';

  ngOnInit() {
    this.loadUserData();
  }

  loadUserData() {
    const user = this.authService.currentUser();
    if (!user) return;

    this.userRoles = user.roles || [];
    this.userName = user.fullName || '';

    if (this.userRoles.length > 1) {
      this.showRoleSelector = true;
    }
  }

  openSelector() {
    this.showRoleSelector = true;
  }

  onRoleSelected(role: Role) {
    this.navigationService.saveRolePreference(role);
  }

  onSelectorClose() {
    this.showRoleSelector = false;
  }

  logout(): void {
    this.logoutState = 'loading';
    this.logoutService.logout().subscribe({
        complete: () => this.logoutState = 'success'
    });
  }
}
