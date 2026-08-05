export interface ChangeUsersStatus {
  newStatus: string,
  totalUpdated: number
}

export interface ActivateUsers {
  activate_users: ChangeUsersStatus
}

export interface DeleteUsers {
  delete_users : ChangeUsersStatus
}

export interface DisableUsers {
  disable_users : ChangeUsersStatus
}

export interface TemporaryDisableUsers {
  temporary_disable_users: ChangeUsersStatus
}
