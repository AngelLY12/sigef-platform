import { CanActivateFn, Router } from "@angular/router";
import { inject } from "@angular/core";
import { NAVIGATION } from "../navigation/navigation.config";
import { AuthService } from "../api/auth.api.service";

export const protectedGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (!authService.isAuthenticated()) {
    return router.createUrlTree([NAVIGATION.auth.login]);
  }

  return true;
};
