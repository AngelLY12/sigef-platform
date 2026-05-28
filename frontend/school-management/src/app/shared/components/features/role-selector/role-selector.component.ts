import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { Role } from '../../../../core/models/enums/role.enum';
import { ButtonComponent } from '../../ui/button/button.component';

@Component({
  selector: 'app-role-selector',
  standalone: true,
  imports: [CommonModule, ButtonComponent],
  templateUrl: './role-selector.component.html',
  styleUrl: './role-selector.component.scss'
})
export class RoleSelectorComponent {
  @Input() show = false;
  @Input() roles: Role[] = [];
  @Input() userName: string = '';
  @Output() roleSelected = new EventEmitter<Role>();
  @Output() close = new EventEmitter<void>();

  Role = Role;

  getRoleIcon(role: Role): string {
    const icons: Record<Role, string> = {
      [Role.ADMIN]: 'admin_panel_settings',
      [Role.STUDENT]: 'school',
      [Role.PARENT]: 'family_history',
      [Role.APPLICANT]: 'assignment',
      [Role.FINANCIAL_STAFF]: 'account_balance',
      [Role.SUPERVISOR]: 'supervisor_account',
      [Role.UNVERIFIED]: 'verified'
    };
    return icons[role] || 'account_circle';
  }

  getRoleName(role: Role): string {
    const names: Record<Role, string> = {
      [Role.ADMIN]: 'Administrador',
      [Role.STUDENT]: 'Estudiante',
      [Role.PARENT]: 'Padre/Madre',
      [Role.APPLICANT]: 'Aspirante',
      [Role.FINANCIAL_STAFF]: 'Personal Financiero',
      [Role.SUPERVISOR]: 'Supervisor',
      [Role.UNVERIFIED]: 'Por verificar'
    };
    return names[role] || role;
  }

  getRoleDescription(role: Role): string {
    const descriptions: Record<Role, string> = {
      [Role.ADMIN]: 'Gestión completa del sistema',
      [Role.STUDENT]: 'Ver cursos, calificaciones y más',
      [Role.PARENT]: 'Seguimiento de tus hijos',
      [Role.APPLICANT]: 'Proceso de admisión',
      [Role.FINANCIAL_STAFF]: 'Gestión de pagos y finanzas',
      [Role.SUPERVISOR]: 'Supervisión de procesos',
      [Role.UNVERIFIED]: 'Pendiente de verificación'
    };
    return descriptions[role] || 'Acceso al dashboard';
  }

  onSelectRole(role: Role) {
    this.roleSelected.emit(role);
  }

  onClose() {
    this.close.emit();
  }

}
