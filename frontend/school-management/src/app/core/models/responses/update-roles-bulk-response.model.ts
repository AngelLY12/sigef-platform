export interface UpdateRolesBulkResponse {
  users_roles: UsersRoles
}

export interface UsersRoles {
  summary: RolesSummary;
  users: UsersSummary;
  rolesProcessed: RolesProcessed

}

export interface RolesSummary {
  totalFound: number;
  totalUpdated: number;
  totalUnchanged: number;
  totalFailed: number;
  operations: {
    total_roles_removed: string[];
    total_roles_added: string[];
    total_chunks_processed: number
  }
}

export interface UsersSummary {
  processed_users_id: number[];
  affected_users_id: number[];
  unchanged_users_id: number[];
  failed_users_id: number[];

}

export interface RolesProcessed {
  processed_added: string[];
  processed_removed: string[]

}
