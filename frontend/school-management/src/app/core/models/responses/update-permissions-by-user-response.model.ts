export interface UpdatePermissionsByUserResponse {
  updated: PermissionsByUser;
}

export interface PermissionsByUser {
  userId: number;
  fullName: string;
  permissions: {
    permissionsAdded: string[];
    permissionsRemoved: string[];
    currentPermissions: string[];
  }
}
