import { ADMIN_NAVIGATION } from "./admin-navigation.config";
import { AUTH_NAVIGATION } from "./auth-navigation.config";
import { CLIENT_NAVIGATION } from "./client-navigation.config";
import { COMMON_NAVIGATION } from "./common-navigation.config";
import { FINANCIAL_NAVIGATION } from "./financial-staff-navigation.config";
import { NOTIFICATIONS_NAVIGATION } from "./notifications-navigation.config";
import { PROFILE_NAVIGATION } from "./profile-navigation.config";

export const NAVIGATION = {
  auth: AUTH_NAVIGATION,
  common: COMMON_NAVIGATION,
  client: CLIENT_NAVIGATION,
  financial: FINANCIAL_NAVIGATION,
  admin: ADMIN_NAVIGATION,
  profile: PROFILE_NAVIGATION,
  notifications: NOTIFICATIONS_NAVIGATION
}
