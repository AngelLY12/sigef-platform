import { PERMISSIONS_UI } from '../config/permissions-ui.config';
import { Permission } from '../../features/admin/models/response/permissions/permissions.model';
import { PermissionsByCurps } from '../../features/admin/models/response/permissions/permissions-by-curp-response.model';
import { PermissionsByRole } from '../../features/admin/models/response/permissions/permissions-by-role-response.model';
import { Permission as PermissionType } from '../models/types/permissions.type';
import { GroupedOption } from '../../shared/components/form/selector/group-state-selector-list/grouped-option.config.model';
import { SelectOption } from '../../shared/components/form/controls/select/select-option.config.model';

export class PermissionsHelper {
  static mapPermissionsByCurpToOptions(
    data: PermissionsByCurps,
  ): { label: string; value: string }[] {
    if (!data) return [];

    const allPermissions = data.permissions.flatMap((rp) => rp.permissions);

    const unique = Array.from(
      new Map(allPermissions.map((p) => [p.name, p])).values(),
    );

    return unique.map((p) => ({
      label: p.label,
      value: p.name,
    }));
  }

  static mapPermissionsByRoleToOptions(
    data: PermissionsByRole,
  ): { label: string; value: string }[] {
    if (!data) return [];

    const allPermissions = data.permissions;

    const unique = Array.from(
      new Map(allPermissions.map((p) => [p.name, p])).values(),
    );

    return unique.map((p) => ({
      label: p.label,
      value: p.name,
    }));
  }

  static getPermissionsTranslate(): { label: string; value: PermissionType }[] {
    return Object.entries(PERMISSIONS_UI).map(([value, meta]) => ({
      value: value as PermissionType,
      label: meta.label,
    }));
  }

  static toGroupedPermissions(permissions: Permission[]): GroupedOption[] {
    const groups: Record<string, SelectOption[]> = {};

    permissions.forEach((p) => {
      const groupName = p.group ?? 'Otros';

      if (!groups[groupName]) {
        groups[groupName] = [];
      }

      groups[groupName].push({
        label: p.label,
        value: p.name,
      });
    });

    return Object.entries(groups).map(([group, items]) => ({
      group,
      items,
    }));
  }

  static getLabel(permission: PermissionType): string {
    return PERMISSIONS_UI[permission]?.label || permission;
  }

  static getGroup(permission: PermissionType): string {
    return PERMISSIONS_UI[permission]?.group || 'Otros';
  }
}
