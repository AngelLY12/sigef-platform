import { Permission } from "../domain/permissions.model";

export interface PermissionsByRoleResponse {
  permissions: PermissionsByRole;
}

export interface PermissionsByRole {
  role: string;
  usersCount: number;
  permissions: Permission[]
}
