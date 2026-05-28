import { environment } from './../../../environments/environment';

export const BASE_URL = environment.apiUrl;
export const API_URL = `${BASE_URL}/api/v1`;
export const SYSTEM_URL = `${BASE_URL}/api/health`;
export const ADMIN_URL = `${API_URL}/admin-actions`;
export const PROFILE_URL = `${API_URL}/users`;
export const CAREER_URL = `${API_URL}/careers`;
export const NOTIFICATIONS_URL = `${API_URL}/notifications`;
export const STUDENTS_URL = {
  dashboard: `${API_URL}/dashboard`
};
export const FINANCIAL_STAFF_URLS = {
  dashboard: `${API_URL}/dashboard-staff`,
  concepts: `${API_URL}/concepts`,
  debts: `${API_URL}/debts`
};

