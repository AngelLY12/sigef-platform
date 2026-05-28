import { Permission } from "../domain/permissions.model";

export interface PermissionsByUserResponse {
  permissions: Permission[];
}
