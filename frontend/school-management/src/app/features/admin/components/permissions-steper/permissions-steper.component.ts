import { CommonModule } from '@angular/common';
import { Component, inject, Input } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { PermissionsHelper } from '../../../../core/utils/permissions-helper.utils';
import { AdminService } from '../../../../core/api/admin.api.service';
import { RolesHelper } from '../../../../core/utils/roles-helper';
import { ModalService } from '../../../../core/services/modal.service';
import { SelectComponent } from '../../../../shared/components/form/select/select.component';
import { RadioGroupComponent } from '../../../../shared/components/form/radio-group/radio-group.component';
import { StepperComponent } from '../../../../shared/components/features/stepper/stepper.component';
import { Role } from '../../../../core/models/enums/role.enum';
import { UserListItem } from '../../models/user-list-item.model';
import { UpdatePermissionsBulk } from '../../models/update-permissions-bulk.model';
import { SelectorActionState } from '../../../../core/models/types/permissions-state.type';
import { GroupedOption } from '../../../../core/models/domain/action-field.modal';
import { PermissionsByCurps } from '../../../../core/models/responses/permissions-by-curp-response.model';
import { GroupStateSelectorListComponent } from '../../../../shared/components/form/selector/group-state-selector-list/group-state-selector-list.component';
import { LoadingState } from '../../../../core/models/types/loading-state.type';

@Component({
  selector: 'app-permissions-steper',
  standalone: true,
  imports: [
    CommonModule,
    RadioGroupComponent,
    SelectComponent,
    StepperComponent,
    GroupStateSelectorListComponent,
    FormsModule,
  ],
  templateUrl: './permissions-steper.component.html',
  styleUrl: './permissions-steper.component.scss',
})
export class PermissionsSteperComponent {
  private adminService = inject(AdminService);
  private modalService = inject(ModalService);
  @Input() selectedUsers: UserListItem[] = [];

  currentStep = 0;

  private _mode: 'curps' | 'role' | null = null;

  get mode(): 'curps' | 'role' | null {
    return this._mode;
  }

  set mode(value: 'curps' | 'role' | null) {
    this._mode = value;
    this.resetState();
  }

  selectedRole: Role | null = null;

  permissionsGroups: GroupedOption[] = [];
  rolesOptions = RolesHelper.getRolesOptionsToDisplay();

  permissionsState: Record<string, SelectorActionState> = {};

  loading: LoadingState = 'idle';

  steps: string[] = [];

  get radioOptions() {
    return [
      {
        label: 'Basado en usuarios',
        value: 'curps',
        description: 'Usa los permisos actuales de los usuarios seleccionados',
      },
      {
        label: 'Basado en rol',
        value: 'role',
        description: 'Usa los permisos definidos por un rol',
      },
    ];
  }

  get stepsByMode(): string[] {
    return this.mode === 'role'
      ? ['Fuente', 'Configuración', 'Permisos']
      : ['Fuente', 'Permisos'];
  }

  canProceed(): boolean {
    if (this.currentStep === 0) return !!this.mode;
    if (this.currentStep === 1 && this.mode === 'role')
      return !!this.selectedRole;
    return true;
  }

  nextStep() {
    if (this.currentStep === 0) {
      if (this.mode === 'curps') {
        this.loadPermissionsByCurps();
      }
    }

    if (this.currentStep === 1 && this.mode === 'role') {
      this.loadPermissionsByRole();
    }

    this.currentStep++;
  }

  prevStep() {
    this.currentStep--;
  }

  resetState() {
    this.currentStep = 0;
    this.selectedRole = null;
    this.permissionsGroups = [];
    this.permissionsState = {};
  }

  loadPermissionsByCurps() {
    const curps = this.selectedUsers.map((u) => u.curp);

    this.loading = 'loading';

    this.adminService.getPermissionsByCurps(curps).subscribe({
      next: (res: PermissionsByCurps) => {
        const allPermissions =
          res?.permissions?.flatMap((rp) => rp.permissions) ?? [];


        this.permissionsGroups = PermissionsHelper.toGroupedPermissions(allPermissions);
        this.initPermissionsState();
        this.loading = 'success';
      },
      error: () => (this.loading = 'error'),
    });
  }

  loadPermissionsByRole() {
    if (!this.selectedRole) return;

    this.loading = 'loading';

    this.adminService.getPermissionsByRole(this.selectedRole).subscribe({
      next: (res) => {
        this.permissionsGroups = PermissionsHelper.toGroupedPermissions(
          res.permissions,
        );
        this.initPermissionsState();
        this.loading = 'success';
      },
      error: () => (this.loading = 'error'),
    });
  }

  initPermissionsState() {
    this.permissionsState = {};
    this.permissionsGroups.forEach((group) => {
      group.items.forEach((p) => {
        this.permissionsState[p.value] = 'none';
      });
    });
  }

  onStateChange(event: { value: string; state: SelectorActionState }) {
    this.permissionsState = {
      ...this.permissionsState,
      [event.value]: event.state,
    };
  }

  complete() {
    const curps = this.selectedUsers.map((user) => user.curp);
    const permissionsToAdd = Object.entries(this.permissionsState)
      .filter(([_, state]) => state === 'add')
      .map(([key]) => key);

    const permissionsToRemove = Object.entries(this.permissionsState)
      .filter(([_, state]) => state === 'remove')
      .map(([key]) => key);
    const payload: UpdatePermissionsBulk = {
      curps: curps,
      role: this.selectedRole,
      permissionsToAdd: permissionsToAdd,
      permissionsToRemove: permissionsToRemove,
    };

    this.loading = 'loading';

    this.adminService.updatePermmissionsBulk(payload).subscribe({
      next: (res) => {
        const summary = res.summary;
        this.loading = 'success';
        this.modalService.closeCustom();
        this.modalService.show({
          message: `Resumen de la actualización:
            Actualizados: ${summary.totalUpdated}
            Sin cambios: ${summary.totalUnchanged}
            Fallidos: ${summary.totalFailed}
          `,
          type: 'success',
          display: 'modal',
        });
      },
      error: () => {
        this.loading = 'error';
      },
    });
  }
}
