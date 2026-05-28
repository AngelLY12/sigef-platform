import { Routes } from '@angular/router';
import { roleGuard } from './core/guards/role.guard';
import { Role } from './core/models/enums/role.enum';
import { protectedGuard } from './core/guards/protected.guard';
import { authGuard } from './core/guards/auth.guard';
import { maintenanceGuard } from './core/guards/maintenance.guard';

export const routes: Routes = [

  {
    path: '',
    redirectTo: 'auth/login',
    pathMatch: 'full'
  },

  {
    path: 'auth',
    loadChildren: () =>
      import('./features/auth/auth.routes')
    .then(m => m.AUTH_ROUTES),
    canActivate: [maintenanceGuard, authGuard]
  },

   {
    path: 'common',
    loadChildren: () =>
      import('./features/public/public.routes')
    .then(m => m.PUBLIC_ROUTES)
  },

  {
    path: 'admin',
      loadChildren: () =>
      import('./features/admin/admin.routes')
    .then(m => m.ADMIN_ROUTES),
    canActivate: [maintenanceGuard,protectedGuard, roleGuard],
    data: {roles: [Role.ADMIN, Role.SUPERVISOR]}
  },

  {
    path: 'financial',
    loadChildren: () =>
      import('./features/financial/financial.routes')
    .then(m => m.FINANCIAL_ROUTES),
    canActivate: [maintenanceGuard,protectedGuard, roleGuard],
    data: {roles: [Role.FINANCIAL_STAFF]}
  },

  {
    path: 'client',
    loadChildren: () =>
      import('./features/client/client.routes')
    .then(m => m.CLIENT_ROUTES),
    canActivate: [maintenanceGuard, protectedGuard, roleGuard],
    data: {roles: [Role.STUDENT, Role.APPLICANT, Role.PARENT]}
  },
  {
    path: 'profile',
    loadChildren: () =>
      import('./features/profile/profile.routes')
    .then(m => m.PROFILE_ROUTES),
    canActivate: [maintenanceGuard,protectedGuard],
  },
  {
    path: 'notifications',
    loadChildren: () =>
      import('./features/notifications/notifications.routes')
    .then(m => m.NOTIFICATIONS_ROUTE),
    canActivate: [maintenanceGuard, protectedGuard]
  },

  {
    path: '**',
    redirectTo: 'common/404'
  }

];
