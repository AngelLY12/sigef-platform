import { CanActivateFn, Router } from "@angular/router";
import { inject } from "@angular/core";
import { NAVIGATION } from "../navigation/navigation.config";
import { NavigationService } from "../services/navigation.service";
import { AuthService } from "../api/auth.api.service";

export const authGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const navigationService = inject(NavigationService);
  const router = inject(Router);

  const isAuthenticated = authService.isAuthenticated();
  const user = authService.currentUser();

  if (isAuthenticated && user) {

  if (user.roles?.length > 1) {
    return router.createUrlTree([NAVIGATION.common.selector]);
  }

  if (user.roles?.length === 1) {
    navigationService.navigateToRoleDashboard(user.roles[0]);
    return false;
  }

  return router.createUrlTree([NAVIGATION.common.unverified]);
}

  return true;

};
