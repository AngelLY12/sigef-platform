import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { AdminService } from '../../../../core/api/admin/admin.api.service';
import { UserDetails } from '../../models/response/user-details.model';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { ActivatedRoute, Router } from '@angular/router';
import { PageLayoutComponent } from '../../../../shared/components/layout/page-layout/page-layout.component';
import { PermissionsByUserParams } from '../../models/request/permissions-by-user-params.model';
import { CareersService } from '../../../../core/api/academics/careers.api.service';
import { NAVIGATION } from '../../../../core/navigation/navigation.config';
import { FolderTabsComponent } from '../../../../shared/components/navigation/folder-tabs/folder-tabs.component';
import { FolderTab } from '../../../../shared/components/navigation/folder-tabs/folder-tabs-config.model';
import { UserBasicInfoComponent } from '../../components/users/user-details/user-basic-info/user-basic-info.component';
import { UserAcademicInfoComponent } from '../../components/users/user-details/user-academic-info/user-academic-info.component';
import { UserAddressComponent } from '../../components/users/user-details/user-address/user-address.component';
import { UserPermissionsComponent } from '../../components/users/user-details/user-permissions/user-permissions.component';
import { UserRolesComponent } from '../../components/users/user-details/user-roles/user-roles.component';
import { UserDetailsActionService } from '../../services/user-details-actions.service';
import { USER_DETAILS_TABS } from '../../config/tabs.config';

@Component({
  selector: 'app-user-details',
  imports: [
    CommonModule,
    PageLayoutComponent,
    FolderTabsComponent,
    UserBasicInfoComponent,
    UserAcademicInfoComponent,
    UserAddressComponent,
    UserPermissionsComponent,
    UserRolesComponent,
  ],
  templateUrl: './user-details.component.html',
  styleUrl: './user-details.component.scss',
})
export class UserDetailsComponent implements OnInit {
  private adminService = inject(AdminService);
  private careersService = inject(CareersService);
  private userDetailsActions = inject(UserDetailsActionService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  userDetails: UserDetails | null = null;
  userId: number | null = null;
  userName: string | null = null;
  state: LoadingState = 'idle';
  permissionsState: LoadingState = 'idle';
  studentDetailsState: LoadingState = 'idle';
  refreshData: boolean = false;
  readonly userDetailTabs: FolderTab[] = USER_DETAILS_TABS;
  activeTab = 'general';

  ngOnInit() {
    this.userId = this.loadUserIdFromRoute();
    this.userName = this.loadUserNameFromRoute();
    if (!this.userId) return;
    this.loadUserDetails(this.userId);
  }

  loadUserDetails(id: number) {
    this.state = 'loading';
    this.adminService.getUserDetails(id, this.refreshData).subscribe({
      next: (details) => {
        this.userDetails = details;
        this.state = 'success';
        this.refreshData = false;
      },
      error: (error) => {
        this.state = 'error';
        this.refreshData = false;
      },
    });
  }

  onRefreshData() {
    this.refreshData = true;

    if (this.userId) {
      this.loadUserDetails(this.userId);
    }
  }

  onUsersNavigation() {
    this.router.navigate([NAVIGATION.admin.users]);
  }

  loadUserIdFromRoute(): number | null {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (!idParam) {
      this.state = 'error';
      return null;
    }
    return +idParam;
  }

  loadUserNameFromRoute(): string | null {
    const nameParam = this.route.snapshot.queryParamMap.get('fullName');
    return nameParam ? decodeURIComponent(nameParam) : null;
  }

  onManageStudentDetails(): void {
    if (!this.userId || !this.userDetails) return;

    this.studentDetailsState = 'loading';

    this.careersService.getCareers().subscribe({
      next: (careers) => {
        this.studentDetailsState = 'success';

        this.userDetailsActions.openStudentDetailsModal(
          this.userId!,
          this.userDetails!,
          careers,
          () => this.loadUserDetails(this.userId!),
        );
      },

      error: () => {
        this.studentDetailsState = 'error';
      },
    });
  }

  onUpdateRoles(): void {
    if (!this.userId || !this.userDetails) return;

    this.userDetailsActions.openRolesModal(this.userId, this.userDetails, () =>
      this.loadUserDetails(this.userId!),
    );
  }

  onUpdatePermissions(): void {
    if (!this.userId || !this.userDetails) return;

    this.permissionsState = 'loading';

    this.adminService
      .getPermissionsByUser(this.userId, this.buildPermissionsParams())
      .subscribe({
        next: (permissions) => {
          this.permissionsState = 'success';

          this.userDetailsActions.openPermissionsModal(
            this.userId!,
            this.userDetails!,
            permissions,
            () => this.loadUserDetails(this.userId!),
          );
        },

        error: () => {
          this.permissionsState = 'error';
        },
      });
  }

  private buildPermissionsParams(): PermissionsByUserParams {
    return {
      roles: this.userDetails?.roles ?? [],
      forceRefresh: false,
    };
  }
}
