import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { AdminService } from '../../../../core/api/admin.api.service';
import { UserDetails } from '../../models/user-details.model';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { ActivatedRoute, Router } from '@angular/router';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { InfoCardComponent } from '../../../../shared/components/data-display/info-card/info-card.component';
import { InfoCardItemComponent } from '../../../../shared/components/data-display/info-card-item/info-card-item.component';
import { RolesHelper } from '../../../../core/utils/roles-helper';
import { PermissionsHelper } from '../../../../core/utils/permissions-helper.utils';
import { Permission } from '../../../../core/models/domain/permissions.model';
import { PermissionsByUserParams } from '../../../../core/models/domain/permissions-by-user-params.model';
import { ModalService } from '../../../../core/services/modal.service';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { SelectorActionState } from '../../../../core/models/types/permissions-state.type';
import { Role } from '../../../../core/models/enums/role.enum';
import { CareersService } from '../../../../core/api/careers.api.service';
import { CareersResponse } from '../../../../core/models/responses/careers-response.model';
import {
  AttachStudentDetailsParams,
  UpdateStudentDetailsParams,
} from '../../../../core/models/domain/student-details-params.model';
import { RolesByUser } from '../../../../core/models/responses/update-roles-by-user-response.model';
import { PermissionsByUser } from '../../../../core/models/responses/update-permissions-by-user-response.model';
import { NAVIGATION } from '../../../../core/navigation/navigation.config';
import { ExpandableSectionComponent } from '../../../../shared/components/layout/expandable-section/expandable-section.component';
import { SelectOption } from '../../../../core/models/domain/action-field.modal';

@Component({
  selector: 'app-user-details',
  imports: [
    CommonModule,
    PageLayoutComponent,
    InfoCardComponent,
    InfoCardItemComponent,
    ButtonComponent,
    ExpandableSectionComponent
  ],
  templateUrl: './user-details.component.html',
  styleUrl: './user-details.component.scss',
})
export class UserDetailsComponent implements OnInit {
  private adminService = inject(AdminService);
  private careersService = inject(CareersService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private modalService = inject(ModalService);

  userDetails: UserDetails | null = null;
  userId: number | null = null;
  userName: string | null = null;
  permissions: Permission[] = [];
  roles: Role[] = [];
  state: LoadingState = 'idle';
  permissionsState: LoadingState = 'idle';
  studentDetailsState: LoadingState = 'idle';
  updateMode: boolean = false;
  forceRefresh = false;
  ngOnInit() {
    this.userId = this.loadUserIdFromRoute();
    this.userName = this.loadUserNameFromRoute();
    if (!this.userId) return;

    this.loadUserDetails(this.userId);
  }

  loadUserDetails(id: number) {
    this.state = 'loading';
    this.adminService.getUserDetails(id).subscribe({
      next: (details) => {
        this.userDetails = details;
        this.state = 'success';
      },
      error: (error) => {
        this.state = 'error';
      },
    });
  }

  onUpdateState() {
    this.updateMode = !this.updateMode;
  }

  onUsersNavigation()
  {
    this.router.navigate([NAVIGATION.admin.users]);
  }

  loadPermissionsByUser() {
    if (!this.userId || !this.userDetails) return;

    this.permissionsState = 'loading';

    this.adminService
      .getPermissionsByUser(this.userId, this.buildPermissionsParams())
      .subscribe({
        next: (permissions) => {
          this.permissions = permissions;
          this.permissionsState = 'success';

          this.openPermissionsModal();
        },
        error: () => {
          this.permissionsState = 'error';
        },
      });
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

  onManageStudentDetails() {
    if (!this.userId) return;
    this.studentDetailsState = 'loading';
    this.careersService.getCareers().subscribe({
      next: (careers: CareersResponse[]) => {
        this.studentDetailsState = 'success';
        this.openStudentDetailsModal(careers);
      },
    });
  }

  openStudentDetailsModal(careers: CareersResponse[]) {
    const detail = this.userDetails?.studentDetail;
    const careerOptions = careers.map((c) => ({
      label: c.career_name,
      value: c.id,
    }));

    this.modalService.openActions(
      {
        title: this.hasStudentDetails
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
          if (this.hasStudentDetails) {
            const payload: UpdateStudentDetailsParams = {
              career_id: data.career_id,
              group: data.group,
              workshop: data.workshop,
            };

            return this.adminService.updateStudentDetails(
              this.userId!,
              payload,
            );
          }

          const payload: AttachStudentDetailsParams = {
            user_id: this.userId!,
            career_id: data.career_id,
            n_control: data.n_control,
            semestre: data.semestre,
            group: data.group,
            workshop: data.workshop,
          };

          return this.adminService.attachStudentDetails(payload);
        },

        onSuccess: (message) => {
          this.loadUserDetails(this.userId!);

          this.modalService.show({
            message,
            type: 'success',
            display: 'modal',
          });
        },

        onFailure: () => {},
      },
      [{ id: this.userId }],
    );
  }

  onUpdateRoles() {
    const initialState = this.initRolesState();
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
            assigned: this.userDetails?.roles ?? [],
          },
        ],
        onSubmit: (data) => {
          const state = data.rolesState;

          const rolesToAdd = Object.entries(state)
            .filter(([_, v]) => v === 'add')
            .map(([k]) => k);

          const rolesToRemove = Object.entries(state)
            .filter(([_, v]) => v === 'remove')
            .map(([k]) => k);

          return this.adminService.updateRolesByUser(this.userId!, {
            rolesToAdd,
            rolesToRemove,
          });
        },
        onSuccess: (res: RolesByUser) => {
          const added = res?.roles?.rolesAdded ?? [];
          const removed = res?.roles?.rolesRemoved ?? [];

          this.loadUserDetails(this.userId!);
          this.modalService.closeActions();
          this.modalService.show({
            type: 'success',
            display: 'modal',
            message: this.buildRolesMessage(added, removed),
          });
        },
      },
      [{ id: this.userId, roles: this.userDetails?.roles }],
    );
  }

  onUpdatePermissions() {
    this.permissions = [];
    this.permissionsState = 'loading';
    this.loadPermissionsByUser();
  }

  initPermissionsState(): Record<string, SelectorActionState> {
    const state: Record<string, SelectorActionState> = {};

    for (const p of this.permissions ?? []) {
      state[p.name] = 'none';
    }

    return state;
  }

  initRolesState(): Record<string, SelectorActionState> {
    const state: Record<string, SelectorActionState> = {};

    for (const r of this.roles ?? []) {
      state[r] = 'none';
    }

    return state;
  }

  openPermissionsModal() {
    const permissionsSnapshot = [...this.permissions];

    const groupedOptions = PermissionsHelper.toGroupedPermissions(permissionsSnapshot);

    const initialState = this.initPermissionsState();
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
            assigned: this.userDetails?.permissions ?? [],
          },
        ],
        onSubmit: (data) => {
          const state = data.permissionsState;

          const permissionsToAdd = Object.entries(state)
            .filter(([_, v]) => v === 'add')
            .map(([k]) => k);

          const permissionsToRemove = Object.entries(state)
            .filter(([_, v]) => v === 'remove')
            .map(([k]) => k);

          return this.adminService.updatePermmissionsByUser(this.userId!, {
            permissionsToAdd,
            permissionsToRemove,
          });
        },
        onSuccess: (res: PermissionsByUser) => {
          const added = res.permissions.permissionsAdded;
          const removed = res.permissions.permissionsRemoved;

          this.loadUserDetails(this.userId!);
          this.modalService.closeActions();

          this.modalService.show({
            type: 'success',
            display: 'modal',
            message: `
          Permisos actualizados correctamente.

          Agregados: ${added.length > 0 ? added.length : 'ninguno'}
          Eliminados: ${removed.length > 0 ? removed.length : 'ninguno'}
          `,
          });
        },
      },
      [{ id: this.userId, permissions: this.userDetails?.permissions }],
    );
  }

  get basicInfoItems() {
    return [
      {
        icon: 'phone',
        label: 'Número de teléfono',
        value: this.userDetails?.basicInfo.phone_number || 'No disponible',
      },
      {
        icon: 'cake',
        label: 'Fecha de nacimiento',
        value: this.userDetails?.basicInfo.birthdate || 'No disponible',
      },
      {
        icon: 'calendar_today',
        label: 'Edad',
        value: this.userDetails?.basicInfo.age.toString() || 'No disponible',
      },
    ];
  }

  get addressInfoItems() {
    const address = this.userDetails?.basicInfo.address;
    if (!address) return [];
    return [
      {
        icon: 'markunread_mailbox',
        label: 'Código Postal',
        value: this.userDetails?.basicInfo?.address?.cp || 'No disponible',
      },
      {
        icon: 'map',
        label: 'Estado',
        value: this.userDetails?.basicInfo?.address?.state || 'No disponible',
      },
      {
        icon: 'location_city',
        label: 'Municipio',
        value: this.userDetails?.basicInfo?.address?.city || 'No disponible',
      },
      {
        icon: 'apartment',
        label: 'Colonia',
        value:
          this.userDetails?.basicInfo?.address?.neighborhood || 'No disponible',
      },
      {
        icon: 'route',
        label: 'Calle',
        value: this.userDetails?.basicInfo?.address?.street || 'No disponible',
      },
      {
        icon: 'home',
        label: 'Número',
        value: this.userDetails?.basicInfo?.address?.number || 'No disponible',
      },
    ];
  }

  get rolesItems() {
    return (
      this.userDetails?.roles.map((role) => ({
        icon: 'badge',
        label: 'Role',
        value: RolesHelper.getLabel(role),
      })) || []
    );
  }

  get permissionsItems() {
    return (
      this.userDetails?.permissions.map((permission) => ({
        icon: 'verified',
        label: PermissionsHelper.getGroup(permission),
        value: PermissionsHelper.getLabel(permission),
      })) || []
    );
  }

  get studentDetailItems() {
    if (!this.userDetails?.studentDetail) return [];
    const detail = this.userDetails.studentDetail;
    return [
      {
        icon: 'menu_book',
        label: 'Carrera',
        value: detail.careerName || 'No disponible',
      },
      {
        icon: 'badge',
        label: 'Número de control',
        value: detail.nControl || 'No disponible',
      },
      {
        icon: 'school',
        label: 'Semestre',
        value: detail.semestre.toString() || 'No disponible',
      },
      {
        icon: 'group',
        label: 'Grupo',
        value: detail.group || 'No disponible',
      },
      {
        icon: 'work',
        label: 'Taller',
        value: detail.workshop || 'No disponible',
      },
    ];
  }

  get hasStudentDetails(): boolean {
    return !!this.userDetails?.studentDetail;
  }

  get hasPermissions(): boolean {
    return !!this.userDetails?.permissions;
  }

  private buildPermissionsParams(): PermissionsByUserParams {
    return {
      roles: this.userDetails?.roles ?? [],
      forceRefresh: this.forceRefresh,
    };
  }
  private buildRolesMessage(
    added: string[] = [],
    removed: string[] = [],
  ): string {
    const addedText = added.length
      ? `Agregados: ${added.map((r) => RolesHelper.translateRole(r)).join(', ')}`
      : '';

    const removedText = removed.length
      ? `Eliminados: ${removed.map((r) => RolesHelper.translateRole(r)).join(', ')}`
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
