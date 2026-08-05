import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { Role } from '../../../../core/models/enums/role.enum';
import { ButtonComponent } from '../../ui/button/button.component';
import { BaseSelectorComponent } from '../base-selector/base-selector.component';
import { SelectorItem } from '../base-selector/selector-item.model';

@Component({
  selector: 'app-role-selector',
  standalone: true,
  imports: [CommonModule, BaseSelectorComponent],
  templateUrl: './role-selector.component.html',
  styleUrl: './role-selector.component.scss',
})
export class RoleSelectorComponent {
  @Input() show = false;
  @Input() roles: Role[] = [];
  @Input() userName: string = '';
  @Output() roleSelected = new EventEmitter<Role>();
  @Output() close = new EventEmitter<void>();

  get roleItems(): SelectorItem<Role>[] {
    return this.roles.map((role) => ({
      id: role,
      title: this.getRoleName(role),
      description: this.getRoleDescription(role),
      icon: this.getRoleIcon(role),
      colorClass: role.toLowerCase(),
      data: role
    }));
  }

  getRoleIcon(role: Role): string {
    const icons: Record<Role, string> = {
      [Role.ADMIN]: 'admin_panel_settings',
      [Role.STUDENT]: 'school',
      [Role.PARENT]: 'family_history',
      [Role.APPLICANT]: 'assignment',
      [Role.FINANCIAL_STAFF]: 'account_balance',
      [Role.SUPERVISOR]: 'supervisor_account',
      [Role.UNVERIFIED]: 'verified',
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
      [Role.UNVERIFIED]: 'Por verificar',
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
      [Role.UNVERIFIED]: 'Pendiente de verificación',
    };
    return descriptions[role] || 'Acceso al dashboard';
  }

  onSelected(item: SelectorItem): void {
    this.roleSelected.emit(item.id as Role);
  }

  onClose() {
    this.close.emit();
  }
}
