import { Permission } from "../domain/permissions.model";

export interface PermissionsByCurpsResponse {
  permissions: PermissionsByCurps;
}

export type PermissionsByCurps = {
  roles: string[];
  users: UserPermissions[];
  permissions: RolePermissions[];
} | null;

export interface RolePermissions {
  role: string;
  permissions: Permission[];
}

export interface UserPermissions {
  id: number;
  curp: string;
  roles: string[];
}


