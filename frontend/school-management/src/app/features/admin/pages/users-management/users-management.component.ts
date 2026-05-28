import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { AdminService } from '../../../../core/api/admin.api.service';
import { UserListItem } from '../../models/user-list-item.model';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { Paginated } from '../../../../core/utils/paginated-helper.utils';
import { PaginatorComponent } from '../../../../shared/components/data-display/paginator/paginator.component';
import { RecordListComponent } from '../../../../shared/components/data-display/record-list/record-list.component';
import { UserDetails } from '../../models/user-details.model';
import { NAVIGATION } from '../../../../core/navigation/navigation.config';
import { Router } from '@angular/router';
import { SelectComponent } from '../../../../shared/components/form/select/select.component';
import { StatusHelper } from '../../../../core/utils/status-helper';
import { createUserListParams, UserListParams } from '../../../../core/models/domain/user-list-params.model';
import { FormsModule } from '@angular/forms';
import { ListController } from '../../../../core/utils/list-controller.utils';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { FilterBarComponent } from '../../../../shared/components/features/filter-bar/filter-bar.component';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { SelectionHelper } from '../../../../core/utils/selection-helpter.utils';
import { CheckboxComponent } from '../../../../shared/components/form/checkbox/checkbox.component';
import { ModalService } from '../../../../core/services/modal.service';
import { SpinnerComponent } from '../../../../shared/components/ui/spinner/spinner.component';
import { MenuComponent } from '../../../../shared/components/navigation/menu/menu.component';
import { MenuItemComponent } from '../../../../shared/components/navigation/menu-item/menu-item.component';
import { Observable } from 'rxjs';
import { Status } from '../../../../core/models/enums/status.enum';
import { RolesHelper } from '../../../../core/utils/roles-helper';
import { UsersRoles } from '../../../../core/models/responses/update-roles-bulk-response.model';
import { PermissionsByCurps } from '../../../../core/models/responses/permissions-by-curp-response.model';
import { PermissionsByRole } from '../../../../core/models/responses/permissions-by-role-response.model';
import { BulkHelper } from '../../../../core/utils/bulk-helper.utils';
import { PermissionsSteperComponent } from '../../components/permissions-steper/permissions-steper.component';

@Component({
  selector: 'app-users-management',
  standalone: true,
  imports: [CommonModule, PageLayoutComponent, CheckboxComponent, RecordListComponent, PaginatorComponent, SelectComponent, FilterBarComponent, ButtonComponent, FormsModule, MenuComponent, MenuItemComponent, SpinnerComponent],
  templateUrl: './users-management.component.html',
  styleUrl: './users-management.component.scss'
})
export class UsersManagementComponent implements OnInit {
  private adminService = inject(AdminService);
  private modalService = inject(ModalService);
  private router = inject(Router);
  private listController!: ListController<UserListParams>;
  paginatedUsers: Paginated<UserListItem> | null = null;
  usersState: LoadingState = 'idle';
  changeStatusState: LoadingState = 'idle';
  userDetails: UserDetails | null = null;
  status = StatusHelper.getStatusOptions();
  userListParams: UserListParams = createUserListParams();
  selectedUsers: UserListItem[] = [];

  //Roles
  selectedRole: string | null = null;

  //Permisos
  permissionsByCurps!: PermissionsByCurps;
  permissionsByRole!: PermissionsByRole;
  mode: 'curps' | 'role' | null = null;
  permissionsOptions: { label: string; value: string }[] = [];
  loadingPermissions: LoadingState = 'idle';

  getStatusColor(status: string): string {
    switch (status) {
      case Status.ACTIVO:
        return 'success';
      case Status.ELIMINADO:
        return 'error';
      case Status.BAJA:
      case Status.BAJA_TEMPORAL:
        return 'warning';
      default:
        return 'default';
    }
  }

  ngOnInit() {
    this.listController = new ListController<UserListParams>(
      () => this.userListParams,
      (params) => this.userListParams = params,
      () => this.loadUsers()
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
      }
    });
  }

  onClickUser(user: UserListItem) {
    this.router.navigate(
      NAVIGATION.admin.userDetails(user.id),
      {
        queryParams: { fullName: user.fullName }
      }
    );
  }

  onDeleteSelected() {
    this.runBulk(
      (ids) => this.adminService.deleteUsers(ids),
      (total) => `Se han eliminado ${total} usuarios`
    );
  }

  onActivateSelected() {
    this.runBulk(
      (ids) => this.adminService.activateUsers(ids),
      (total) => `Se han activado ${total} usuarios`
    );
  }

  onDisableSelected() {
    this.runBulk(
      (ids) => this.adminService.disableUsers(ids),
      (total) => `Se han dado de baja ${total} usuarios`
    );
  }

  onTemporaryDisableSelected() {
    this.runBulk(
      (ids) => this.adminService.temporaryDisableUsers(ids),
      (total) => `Se han dado de baja temporal ${total} usuarios`
    );
  }

  onPromoteStudents(){
    this.adminService.promoteStudents().subscribe({
      next: (res) => {
        this.modalService.show({
          message: res,
          display:'modal',
          type:'success'})
      }
    })
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
    const updatedParams = QueryParamsHelper.changePage(this.userListParams, newPage);
    this.listController.update(updatedParams);
  }

  onStatusFilterChange() {
    const updatedParams = QueryParamsHelper.changeStatus(
      this.userListParams,
      this.userListParams.status || null
    );
    this.listController.update(updatedParams);
  }

  onPageSizeChange(newSize: number) {
    const updatedParams = QueryParamsHelper.changePageSize(this.userListParams, newSize);
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

  onUpdateRolesSelected() {
    this.modalService.openActions(
      {
        title: 'Actualizar roles',
        entityName: 'usuarios',
        fields: [
          {
            name: 'rolesState',
            type: 'state-selector',
            label: 'Roles',
            options: RolesHelper.getRolesOptionsToDisplay(),
            fullWidth: true,
            isBulkOperation:true
          },
        ],
        onSubmit: (data) => {

          const state = data.rolesState || {};

          const rolesToAdd = Object.entries(state)
            .filter(([_, v]) => v === 'add')
            .map(([k]) => k);

          const rolesToRemove = Object.entries(state)
            .filter(([_, v]) => v === 'remove')
            .map(([k]) => k);

          const payload = {
            curps: data.models.map((u: any) => u.curp),
            rolesToAdd: rolesToAdd,
            rolesToRemove: rolesToRemove
          };

          return this.adminService.updateRolesBulk(payload);
        },
        onSuccess: (res: UsersRoles) => {
          const summary = res.summary;

          this.modalService.show({ message:
            `
            Resumen:
            Actualizados: ${summary.totalUpdated}
            Sin cambios: ${summary.totalUnchanged}
            Fallidos: ${summary.totalFailed}
          `,
          type: 'success',
          display: 'modal'
          })
          this.loadUsers();
        },
        onFailure: (err) => {
          this.modalService.show({
            message: 'Hubo un error al actualizar los roles',
            type: 'error',
            display: 'modal',
            errors: [err]
          });
        }
      },
      this.selectedUsers
    );
  }

  currentStep = 0;

  onUpdatePermissionsSelected() {
    this.currentStep = 0;
    this.mode = null;
    this.selectedRole;
    this.permissionsOptions = [];

    this.modalService.openCustom({
      title: 'Actualizar permisos',
      component: PermissionsSteperComponent,
      data: {
        selectedUsers: this.selectedUsers
      }
    });
  }

  private runBulk(
    action: (ids: number[]) => Observable<any>,
    getMessage: (total: number) => string
  ) {
    BulkHelper.execute({
      ids: this.selectedUsers.map(u => u.id),
      action,
      setState: (state) => this.changeStatusState = state,

      onSuccess: (response) => {
        this.modalService.show({
          message: getMessage(response.totalUpdated),
          display: 'alert',
          type: 'success'
        });

        this.selectedUsers = [];
        this.loadUsers();
      }
    });
  }
}
