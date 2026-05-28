import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map } from 'rxjs';
import { SystemServiceApiService } from '../api/system-service.api.service';
import { NAVIGATION } from '../navigation/navigation.config';

export const maintenanceGuard: CanActivateFn = () => {
  const systemService = inject(SystemServiceApiService);
  const router = inject(Router);

  return systemService.getSystemStatus().pipe(
    map(() => true),
    catchError(() => {
      router.navigate([NAVIGATION.common.maintenance]);
      return [false];
    }),
  );
};
