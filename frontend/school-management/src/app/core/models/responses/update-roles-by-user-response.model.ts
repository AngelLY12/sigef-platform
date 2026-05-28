export interface UpdateRolesByUserResponse {
  updated: RolesByUser;
}

export interface RolesByUser {
  userId: number;
  fullName: string;
  roles: {
    rolesAdded: string[];
    rolesRemoved: string[];
    currentRoles: string[];
  }
}
