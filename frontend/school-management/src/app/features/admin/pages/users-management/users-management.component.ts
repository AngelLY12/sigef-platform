import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { AdminService } from '../../../../core/api/admin.api.service';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { Paginated } from '../../../../core/utils/paginated-helper.utils';
import { PaginatorComponent } from '../../../../shared/components/data-display/paginator/paginator.component';
import { RecordListComponent } from '../../../../shared/components/data-display/record-list/record-list.component';
import { NAVIGATION } from '../../../../core/navigation/navigation.config';
import { Router } from '@angular/router';
import { StatusHelper } from '../../../../core/utils/status-helper';
import {
  createUserListParams,
  UserListParams,
} from '../../../../core/models/domain/user-list-params.model';
import { FormsModule } from '@angular/forms';
import { ListController } from '../../../../core/utils/list-controller.utils';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { SelectionHelper } from '../../../../core/utils/selection-helpter.utils';
import { CheckboxComponent } from '../../../../shared/components/form/checkbox/checkbox.component';
import { SpinnerComponent } from '../../../../shared/components/ui/spinner/spinner.component';
import { FolderTab } from '../../../../core/models/domain/folder-tabs-config.model';
import { FolderTabsComponent } from '../../../../shared/components/navigation/folder-tabs/folder-tabs.component';
import { UserRecordComponent } from '../../components/users/management/user-record/user-record.component';
import { UserAvatarComponent } from '../../components/users/management/user-avatar/user-avatar.component';
import { UserBulkActionsComponent } from '../../components/users/management/user-bulk-actions/user-bulk-actions.component';
import { UserBulkActionsService } from '../../services/user-bulk-actions.service';
import { USER_MANAGEMENT_TABS } from '../../config/tabs.config';
import { UserListItem } from '../../models/response/user-list-item.model';

@Component({
  selector: 'app-users-management',
  standalone: true,
  imports: [
    CommonModule,
    PageLayoutComponent,
    CheckboxComponent,
    RecordListComponent,
    PaginatorComponent,
    FormsModule,
    SpinnerComponent,
    FolderTabsComponent,
    UserRecordComponent,
    UserAvatarComponent,
    UserBulkActionsComponent,
  ],
  templateUrl: './users-management.component.html',
  styleUrl: './users-management.component.scss',
})
export class UsersManagementComponent implements OnInit {
  private adminService = inject(AdminService);
  private userBulkActions = inject(UserBulkActionsService);
  private router = inject(Router);
  private listController!: ListController<UserListParams>;
  paginatedUsers: Paginated<UserListItem> | null = null;
  usersState: LoadingState = 'idle';
  changeStatusState: LoadingState = 'idle';
  status = StatusHelper.getStatusOptions();
  userListParams: UserListParams = createUserListParams();
  selectedUsers: UserListItem[] = [];
  activeUserTab = '';
  readonly userTabs: FolderTab[] = USER_MANAGEMENT_TABS;


  ngOnInit() {
    this.listController = new ListController<UserListParams>(
      () => this.userListParams,
      (params) => (this.userListParams = params),
      () => this.loadUsers(),
    );
    this.loadUsers();
  }

  loadUsers() {
    this.usersState = 'loading';
    this.adminService.getUsers(this.userListParams).subscribe({
      next: (response) => {
        this.paginatedUsers = response;
        this.selectedUsers = [];
        this.usersState = 'success';
      },
      error: (error) => {
        this.usersState = 'error';
      },
    });
  }

  onClickUser(user: UserListItem) {
    this.router.navigate(NAVIGATION.admin.userDetails(user.id), {
      queryParams: { fullName: user.fullName },
    });
  }

  onDeleteSelected(): void {
    this.userBulkActions.delete(
      this.selectedUsers,
      (state) => (this.changeStatusState = state),
      () => this.afterBulkUpdate(),
    );
  }

  onActivateSelected(): void {
    this.userBulkActions.activate(
      this.selectedUsers,
      (state) => (this.changeStatusState = state),
      () => this.afterBulkUpdate(),
    );
  }

  onDisableSelected(): void {
    this.userBulkActions.disable(
      this.selectedUsers,
      (state) => (this.changeStatusState = state),
      () => this.afterBulkUpdate(),
    );
  }

  onTemporaryDisableSelected(): void {
    this.userBulkActions.temporaryDisable(
      this.selectedUsers,
      (state) => (this.changeStatusState = state),
      () => this.afterBulkUpdate(),
    );
  }

  onPromoteStudents(): void {
    this.userBulkActions.promoteStudents();
  }

  onUpdateRolesSelected(): void {
    this.userBulkActions.openRolesModal(this.selectedUsers, () =>
      this.afterBulkUpdate(),
    );
  }

  onUpdatePermissionsSelected(): void {
    this.userBulkActions.openPermissionsModal(this.selectedUsers);
  }

  private afterBulkUpdate(): void {
    this.selectedUsers = [];
    this.loadUsers();
  }

  isSelected(user: UserListItem): boolean {
    return SelectionHelper.isSelected(this.selectedUsers, user);
  }

  toggleUser(user: UserListItem) {
    this.selectedUsers = SelectionHelper.toggleItem(this.selectedUsers, user);
  }

  toggleAll(checked: boolean) {
    if (checked) {
      this.selectedUsers = [...this.paginatedUsers!.data.items];
    } else {
      this.selectedUsers = [];
    }
  }

  onPageChange(newPage: number) {
    const updatedParams = QueryParamsHelper.changePage(
      this.userListParams,
      newPage,
    );
    this.listController.update(updatedParams);
  }

  onStatusTabChange(status: string) {
    this.activeUserTab = status;
    const updatedParams = QueryParamsHelper.changeStatus(
      this.userListParams,
      status || null,
    );
    this.listController.update(updatedParams);
  }

  onPageSizeChange(newSize: number) {
    const updatedParams = QueryParamsHelper.changePageSize(
      this.userListParams,
      newSize,
    );
    this.listController.update(updatedParams);
  }
  onResetFilters() {
    this.userListParams = createUserListParams();
    this.loadUsers();
  }

  onRefreshData() {
    const updatedParams = QueryParamsHelper.refreshData(this.userListParams);
    this.listController.update(updatedParams);
  }
}
