import { inject } from "@angular/core";
import { CanActivateFn, Router } from "@angular/router";
import { NAVIGATION } from "../navigation/navigation.config";
import { AuthService } from "../api/auth.api.service";

export const roleGuard: CanActivateFn = (route) => {
  const authService = inject(AuthService);
  const router = inject(Router);
  const expectedRoles = route.data?.['roles'] as string[];
  const user = authService.currentUser();
  if(!user){
    return router.createUrlTree([NAVIGATION.auth.login]);
  }
  const hasRole = user.roles.some(role => expectedRoles.includes(role));
  return hasRole ? true : router.createUrlTree([NAVIGATION.common.unauthorized])
}
