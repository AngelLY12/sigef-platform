import { Injectable, inject } from '@angular/core';
import { AdminService } from '../../../core/api/admin.api.service';
import { ModalService } from '../../../core/services/modal.service';
import { UserDetails } from '../models/response/user-details.model';
import { CareersResponse } from '../../../core/models/responses/careers-response.model';
import {
  AttachStudentDetailsParams,
  UpdateStudentDetailsParams,
} from '../../../core/models/domain/student-details-params.model';
import { RolesHelper } from '../../../core/utils/roles-helper';
import { RolesByUser } from '../../../core/models/responses/update-roles-by-user-response.model';
import { PermissionsHelper } from '../../../core/utils/permissions-helper.utils';
import { Permission } from '../../../core/models/domain/permissions.model';
import { PermissionsByUser } from '../../../core/models/responses/update-permissions-by-user-response.model';
import { SelectorActionState } from '../../../core/models/types/permissions-state.type';

@Injectable({ providedIn: 'root' })
export class UserDetailsActionService {
  private adminService = inject(AdminService);
  private modalService = inject(ModalService);

  openStudentDetailsModal(
    userId: number,
    userDetails: UserDetails,
    careers: CareersResponse[],
    onUpdated: () => void,
  ): void {
    const detail = userDetails.studentDetail;

    const careerOptions = careers.map((career) => ({
      label: career.career_name,
      value: career.id,
    }));

    this.modalService.openActions(
      {
        title: detail
          ? 'Actualizar información académica'
          : 'Agregar información académica',

        entityName: 'usuario',

        fields: [
          {
            name: 'career_id',
            type: 'select',
            label: 'Carrera',
            options: careerOptions,
            defaultValue: detail?.careerName ?? null,
          },
          {
            name: 'n_control',
            type: 'input',
            placeHolder: 'Ejemplo: 26000000',
            label: 'Número de control',
            defaultValue: detail?.nControl ?? '',
          },
          {
            name: 'semestre',
            type: 'input',
            placeHolder: '1',
            inputType: 'number',
            label: 'Semestre',
            defaultValue: detail?.semestre ?? null,
          },
          {
            name: 'group',
            type: 'input',
            placeHolder: 'Ejemplo: 8A',
            label: 'Grupo',
            defaultValue: detail?.group ?? '',
          },
          {
            name: 'workshop',
            type: 'input',
            placeHolder: 'Ejemplo: Dibujo',
            label: 'Taller',
            defaultValue: detail?.workshop ?? '',
          },
        ],

        onSubmit: (data) => {
          if (detail) {
            const payload: UpdateStudentDetailsParams = {
              career_id: data.career_id,
              group: data.group,
              workshop: data.workshop,
            };

            return this.adminService.updateStudentDetails(userId, payload);
          }

          const payload: AttachStudentDetailsParams = {
            user_id: userId,
            career_id: data.career_id,
            n_control: data.n_control,
            semestre: data.semestre,
            group: data.group,
            workshop: data.workshop,
          };

          return this.adminService.attachStudentDetails(payload);
        },

        onSuccess: (message) => {
          onUpdated();

          this.modalService.show({
            message,
            type: 'success',
            display: 'modal',
          });
        },

        onFailure: () => {},
      },
      [{ id: userId }],
    );
  }

  openRolesModal(
    userId: number,
    userDetails: UserDetails,
    onUpdated: () => void,
  ): void {
    const initialState = this.createSelectorState(userDetails.roles ?? []);

    this.modalService.openActions(
      {
        title: 'Actualizar roles',
        entityName: 'usuario',

        fields: [
          {
            name: 'rolesState',
            type: 'state-selector',
            label: 'Roles',
            options: RolesHelper.getRolesOptionsToDisplay(),
            fullWidth: true,
            defaultValue: initialState,
            assigned: userDetails.roles ?? [],
          },
        ],

        onSubmit: (data) => {
          const state = data.rolesState;

          const rolesToAdd = Object.entries(state)
            .filter(([_, value]) => value === 'add')
            .map(([key]) => key);

          const rolesToRemove = Object.entries(state)
            .filter(([_, value]) => value === 'remove')
            .map(([key]) => key);

          return this.adminService.updateRolesByUser(userId, {
            rolesToAdd,
            rolesToRemove,
          });
        },

        onSuccess: (res: RolesByUser) => {
          const added = res?.roles?.rolesAdded ?? [];
          const removed = res?.roles?.rolesRemoved ?? [];

          onUpdated();

          this.modalService.closeActions();

          this.modalService.show({
            type: 'success',
            display: 'modal',
            message: this.buildRolesMessage(added, removed),
          });
        },
      },
      [
        {
          id: userId,
          roles: userDetails.roles,
        },
      ],
    );
  }

  openPermissionsModal(
    userId: number,
    userDetails: UserDetails,
    permissions: Permission[],
    onUpdated: () => void,
  ): void {
    const permissionsSnapshot = [...permissions];

    const groupedOptions =
      PermissionsHelper.toGroupedPermissions(permissionsSnapshot);

    const initialState = this.createSelectorState(
      permissionsSnapshot.map((permission) => permission.name),
    );

    this.modalService.openActions(
      {
        title: 'Actualizar Permisos',
        entityName: 'usuario',

        fields: [
          {
            name: 'permissionsState',
            type: 'group-state-selector',
            label: 'Permisos',
            groupOptions: groupedOptions,
            fullWidth: true,
            defaultValue: initialState,
            assigned: userDetails.permissions ?? [],
          },
        ],

        onSubmit: (data) => {
          const state = data.permissionsState;

          const permissionsToAdd = Object.entries(state)
            .filter(([_, value]) => value === 'add')
            .map(([key]) => key);

          const permissionsToRemove = Object.entries(state)
            .filter(([_, value]) => value === 'remove')
            .map(([key]) => key);

          return this.adminService.updatePermmissionsByUser(userId, {
            permissionsToAdd,
            permissionsToRemove,
          });
        },

        onSuccess: (res: PermissionsByUser) => {
          const added = res.permissions.permissionsAdded;
          const removed = res.permissions.permissionsRemoved;

          onUpdated();

          this.modalService.closeActions();

          this.modalService.show({
            type: 'success',
            display: 'modal',
            message: `
Permisos actualizados correctamente.

Agregados: ${added.length > 0 ? added.length : 'ninguno'}
Eliminados: ${removed.length > 0 ? removed.length : 'ninguno'}
            `.trim(),
          });
        },
      },
      [
        {
          id: userId,
          permissions: userDetails.permissions,
        },
      ],
    );
  }

  private createSelectorState(
    values: string[],
  ): Record<string, SelectorActionState> {
    return Object.fromEntries(
      values.map((value) => [value, 'none' as SelectorActionState]),
    );
  }

  private buildRolesMessage(
    added: string[] = [],
    removed: string[] = [],
  ): string {
    const addedText = added.length
      ? `Agregados: ${added
          .map((role) => RolesHelper.translateRole(role))
          .join(', ')}`
      : '';

    const removedText = removed.length
      ? `Eliminados: ${removed
          .map((role) => RolesHelper.translateRole(role))
          .join(', ')}`
      : '';

    if (added.length && removed.length) {
      return `Roles actualizados correctamente.\n\n${addedText}\n${removedText}`;
    }

    if (added.length) {
      return `Roles agregados correctamente.\n\n${addedText}`;
    }

    if (removed.length) {
      return `Roles eliminados correctamente.\n\n${removedText}`;
    }

    return 'No se realizaron cambios en los roles.';
  }
}
