import { Role } from "../../../../core/models/types/role.type";

export interface PermissionsByUserParams {
  roles: Role[];
  forceRefresh: boolean;
}
