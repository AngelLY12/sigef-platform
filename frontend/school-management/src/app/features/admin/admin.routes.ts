import { Routes } from "@angular/router";
import { UsersManagementComponent } from "./pages/users-management/users-management.component";
import { DashboardComponent } from "./pages/dashboard/dashboard.component";
import { MainLayoutComponent } from "../../layouts/main-layout/main-layout.component";
import { UserDetailsComponent } from "./pages/user-details/user-details.component";
import { ImportDataComponent } from "./pages/import-data/import-data.component";

export const ADMIN_ROUTES: Routes = [
  {
    path: '',
    component: MainLayoutComponent,
    children: [
      {
        path: '',
        redirectTo: 'dashboard',
        pathMatch: 'full'
      },
      {
        path: 'dashboard',
        component: DashboardComponent
      },
      {
        path: 'users',
        component: UsersManagementComponent
      },
      {
        path: 'users/:id',
        component: UserDetailsComponent
      },
      {
        path: 'import',
        component: ImportDataComponent
      }

    ]
  }

];
