import { Routes } from "@angular/router";
import { ProfileViewComponent } from "./pages/profile-view/profile-view.component";
import { MainLayoutComponent } from "../../layouts/main-layout/main-layout.component";

export const PROFILE_ROUTES: Routes = [
  {
    path: '',
    component: MainLayoutComponent,
    children: [
      {
        path: '',
        redirectTo: 'view',
        pathMatch: 'full'
      },
      {
        path: 'view',
        component: ProfileViewComponent
      }
    ]
  }


];
