import { environment } from './../../../environments/environment';

export const BASE_URL = environment.apiUrl;
export const API_URL = `${BASE_URL}/api/v1`;
export const SYSTEM_URL = `${BASE_URL}/api/health`;
export const ADMIN_URL = `${API_URL}/admin-actions`;
export const PROFILE_URL = `${API_URL}/users`;
export const CAREER_URL = `${API_URL}/careers`;
export const NOTIFICATIONS_URL = `${API_URL}/notifications`;
export const STUDENTS_URL = {
  dashboard: `${API_URL}/dashboard`,
  pending: `${API_URL}/pending-payments`,
  cards: `${API_URL}/cards`,
  paymentsHistory: `${API_URL}/payments/history`
};
export const FINANCIAL_STAFF_URLS = {
  dashboard: `${API_URL}/dashboard-staff`,
  concepts: `${API_URL}/concepts`,
  debts: `${API_URL}/debts`,
  payments: `${API_URL}/payments`
};
export const PARENTS_URL = {
  invite: `${API_URL}/parents/invite`,
  acceptInvitation: `${API_URL}/parents/invite/accept`,
  children: `${API_URL}/parents/get-children`,
  parents: `${API_URL}/parents/get-parents`,
  removeParent: `${API_URL}/parents/delete-parent`,
};
