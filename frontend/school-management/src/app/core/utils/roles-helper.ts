import { SelectOption } from "../models/domain/action-field.modal";
import { Role } from "../models/enums/role.enum";

export class RolesHelper {
  static roleLabels: Record<string, string> = {
    admin: 'Administrador',
    supervisor: 'Supervisor',
    student: 'Estudiante',
    applicant: 'Aspirante',
    parent: 'Padre',
    'financial-staff': 'Personal financiero',
    unverified: 'No verificado'
  };

  static translateRole(role: string): string {
    return this.roleLabels[role] ?? role;
  }

  static getRolesTranslate(): { label: string; value: Role }[] {
    return [
      {
        label: 'Administrador',
        value: Role.ADMIN
      },
      {
        label: 'Supervisor',
        value: Role.SUPERVISOR
      },
      {
        label: 'Estudiante',
        value: Role.STUDENT
      },
      {
        label: 'Aspirante',
        value: Role.APPLICANT
      },
      {
        label: 'Padre',
        value: Role.PARENT
      },
      {
        label: 'Personal Financiero',
        value: Role.FINANCIAL_STAFF
      },
      {
        label: 'No verificado',
        value: Role.UNVERIFIED
      }
    ];
  }

  static getRolesOptionsToDisplay(): SelectOption[] {
    return [
      {
        label: 'Supervisor',
        value: Role.SUPERVISOR
      },
      {
        label: 'Estudiante',
        value: Role.STUDENT
      },
      {
        label: 'Aspirante',
        value: Role.APPLICANT
      },
      {
        label: 'Padre',
        value: Role.PARENT
      },
      {
        label: 'Personal Financiero',
        value: Role.FINANCIAL_STAFF
      },
    ];

  }

  static getRolesMap(): Record<Role, string> {
    return Object.fromEntries(
      this.getRolesTranslate().map(r => [r.value, r.label])
    ) as Record<Role, string>;
  }

  static getLabel(role: Role): string {
    return this.getRolesMap()[role] || role;
  }

}
