import { Role } from "../types/role.type";

export interface PermissionsByUserParams {
  roles: Role[];
  forceRefresh: boolean;
}
