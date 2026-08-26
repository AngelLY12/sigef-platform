import { Routes } from '@angular/router';
import { UsersManagementComponent } from './pages/users-management/users-management.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { MainLayoutComponent } from '../../layouts/main-layout/main-layout.component';
import { UserDetailsComponent } from './pages/user-details/user-details.component';
import { ImportDataComponent } from './pages/import-data/import-data.component';
import { ADMIN_EVENTS_ROUTES } from './admin-events.routes';
import { NAVIGATION } from '../../core/navigation/navigation.config';

export const ADMIN_ROUTES: Routes = [
  {
    path: '',
    component: MainLayoutComponent,
    children: [
      {
        path: '',
        redirectTo: 'dashboard',
        pathMatch: 'full',
      },
      {
        path: 'dashboard',
        component: DashboardComponent,
        data: {
          title: 'Dashboard',
          breadcrumb: 'Dashboard',
        },
      },
      {
        path: 'users',
        component: UsersManagementComponent,
        data: {
          title: 'Usuarios',
          breadcrumb: 'Usuarios',
        },
      },
      {
        path: 'users/:id',
        component: UserDetailsComponent,
        data: {
          title: 'Detalle de usuario',
          breadcrumb: 'Detalle',
          breadcrumbParam: {
            param: 'id',
            label: 'Usuario',
          },
          breadcrumbParent: {
            label: 'Usuarios',
            url: NAVIGATION.admin.users,
          },
        },
      },
      {
        path: 'import',
        component: ImportDataComponent,
        data: {
          title: 'Importar datos',
          breadcrumb: 'Importar',
        },
      },
      ...ADMIN_EVENTS_ROUTES,
    ],
  },
];
