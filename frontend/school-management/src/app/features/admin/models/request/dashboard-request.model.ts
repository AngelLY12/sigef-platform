export interface DashboardRequest {
  only_this_year: boolean;
  forceRefresh: boolean;
}

export const BASE_DASHBOARD_LIST_PARAMS: Readonly<DashboardRequest> = {
  only_this_year: true,
  forceRefresh: false,
};
