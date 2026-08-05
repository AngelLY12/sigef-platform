export interface UserListParams {
  perPage: number;
  page: number;
  forceRefresh?: boolean;
  status?: string|null;
}

const BASE_USER_LIST_PARAMS: Readonly<UserListParams> = {
  perPage: 15,
  page: 1,
  forceRefresh: false,
  status: null
};

export function createUserListParams(
  overrides: Partial<UserListParams> = {}
): UserListParams {
  return {
    ...BASE_USER_LIST_PARAMS,
    ...overrides
  };
}
