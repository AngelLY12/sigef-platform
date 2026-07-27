import { inject, Injectable } from "@angular/core";
import { AdminService } from "../../../core/api/admin.api.service";
import { ModalService } from "../../../core/services/modal.service";
import { LoadingState } from "../../../core/models/types/loading-state.type";
import { RolesHelper } from "../../../core/utils/roles-helper";
import { UsersRoles } from "../../../core/models/responses/update-roles-bulk-response.model";
import { PermissionsSteperComponent } from "../components/permissions-steper/permissions-steper.component";
import { Observable } from "rxjs";
import { BulkHelper } from "../../../core/utils/bulk-helper.utils";
import { UserListItem } from "../models/response/user-list-item.model";

@Injectable({ providedIn: 'root' })
export class UserBulkActionsService {
   private adminService = inject(AdminService);
  private modalService = inject(ModalService);

  delete(
    users: UserListItem[],
    setState: (state: LoadingState) => void,
    onUpdated: () => void,
  ): void {
    this.runBulk(
      users,
      (ids) => this.adminService.deleteUsers(ids),
      (total) => `Se han eliminado ${total} usuarios`,
      setState,
      onUpdated,
    );
  }

  activate(
    users: UserListItem[],
    setState: (state: LoadingState) => void,
    onUpdated: () => void,
  ): void {
    this.runBulk(
      users,
      (ids) => this.adminService.activateUsers(ids),
      (total) => `Se han activado ${total} usuarios`,
      setState,
      onUpdated,
    );
  }

  disable(
    users: UserListItem[],
    setState: (state: LoadingState) => void,
    onUpdated: () => void,
  ): void {
    this.runBulk(
      users,
      (ids) => this.adminService.disableUsers(ids),
      (total) => `Se han dado de baja ${total} usuarios`,
      setState,
      onUpdated,
    );
  }

  temporaryDisable(
    users: UserListItem[],
    setState: (state: LoadingState) => void,
    onUpdated: () => void,
  ): void {
    this.runBulk(
      users,
      (ids) => this.adminService.temporaryDisableUsers(ids),
      (total) => `Se han dado de baja temporal ${total} usuarios`,
      setState,
      onUpdated,
    );
  }

  promoteStudents(): void {
    this.adminService.promoteStudents().subscribe({
      next: (message) => {
        this.modalService.show({
          message,
          display: 'modal',
          type: 'success',
        });
      },
    });
  }

  openRolesModal(
    users: UserListItem[],
    onUpdated: () => void,
  ): void {
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
            isBulkOperation: true,
          },
        ],

        onSubmit: (data) => {
          const state = data.rolesState || {};

          const rolesToAdd = Object.entries(state)
            .filter(([_, value]) => value === 'add')
            .map(([key]) => key);

          const rolesToRemove = Object.entries(state)
            .filter(([_, value]) => value === 'remove')
            .map(([key]) => key);

          return this.adminService.updateRolesBulk({
            curps: data.models.map((user: UserListItem) => user.curp),
            rolesToAdd,
            rolesToRemove,
          });
        },

        onSuccess: (res: UsersRoles) => {
          const summary = res.summary;

          this.modalService.show({
            message: `
Resumen:
Actualizados: ${summary.totalUpdated}
Sin cambios: ${summary.totalUnchanged}
Fallidos: ${summary.totalFailed}
            `.trim(),
            type: 'success',
            display: 'modal',
          });

          onUpdated();
        },

        onFailure: (err) => {
          this.modalService.show({
            message: 'Hubo un error al actualizar los roles',
            type: 'error',
            display: 'modal',
            errors: [err],
          });
        },
      },
      users,
    );
  }

  openPermissionsModal(users: UserListItem[]): void {
    this.modalService.openCustom({
      title: 'Actualizar permisos',
      component: PermissionsSteperComponent,
      data: {
        selectedUsers: users,
      },
    });
  }

  private runBulk(
    users: UserListItem[],
    action: (ids: number[]) => Observable<any>,
    getMessage: (total: number) => string,
    setState: (state: LoadingState) => void,
    onUpdated: () => void,
  ): void {
    BulkHelper.execute({
      ids: users.map((user) => user.id),

      action,

      setState,

      onSuccess: (response) => {
        this.modalService.show({
          message: getMessage(response.totalUpdated),
          display: 'alert',
          type: 'success',
        });

        onUpdated();
      },
    });
  }
}
