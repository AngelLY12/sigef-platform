import { Routes } from "@angular/router";
import { NotFoundComponent } from "./pages/not-found/not-found.component";
import { UnverifiedComponent } from "./pages/unverified/unverified.component";
import { MaintenanceComponent } from "./pages/maintenance/maintenance.component";
import { UnauthorizedComponent } from "./pages/unauthorized/unauthorized.component";
import { RoleSelectorPageComponent } from "./pages/role-selector-page/role-selector-page.component";
import { protectedGuard } from "../../core/guards/protected.guard";
import { roleGuard } from "../../core/guards/role.guard";

export const PUBLIC_ROUTES: Routes = [
  {
    path: '404',
    component:NotFoundComponent
  },
  {
    path: 'unverified',
    component: UnverifiedComponent

  },
  {
    path: 'maintenance',
    component: MaintenanceComponent
  },
  {
    path: 'unauthorized',
    component: UnauthorizedComponent
  },
  {
    path: 'selector',
    component: RoleSelectorPageComponent,
    canActivate: [protectedGuard],


  },
  {path: '**', redirectTo: '/common/404'}


];
