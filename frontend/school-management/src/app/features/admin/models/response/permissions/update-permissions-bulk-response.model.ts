export interface UpdatePermissionsBulkResponse {
  users_permissions: UsersPermissions;
}

export interface UsersPermissions {
  summary: PermissionsSummary;
  users: UsersPermissions;
  permissionsProcessed: PermissionsProcessed;
}

export interface PermissionsSummary {
  totalFound: number,
  totalUpdated: number,
  totalUnchanged: number,
  totalFailed: number,
  operations: {
    total_permissions_removed: number,
    total_permissions_added: number,
    total_roles_processed: number
  }

}

export interface UsersPermissions {
  processed_users_id: number[],
  affected_users_id: number[],
  failed_users_id: number[],
  unchanged_users_id: number[]
}

export interface PermissionsProcessed {
  processed_added: string[],
  processed_removed: string[]

}
