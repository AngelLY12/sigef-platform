import { Role } from "../../../core/models/enums/role.enum"

export interface UpdatePermissionsBulk {
  curps?: string[],
  role?: Role|null,
  permissionsToAdd?: string[],
  permissionsToRemove?: string[]
}
