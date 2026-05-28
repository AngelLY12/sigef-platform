import { Role } from "../enums/role.enum";
import { Status } from "../types/status.type";

export interface AuthUser {
  id: number;
  fullName: string;
  status: Status;
  roles: Role[];
  hasUnreadNotifications: boolean;
}
