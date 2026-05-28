export interface DashboardParams {
  only_this_year?: boolean;
  forceRefresh?: boolean;
}

const BASE_DASHBOARD_PARAMS: DashboardParams = {
  only_this_year: false,
  forceRefresh: false
}
