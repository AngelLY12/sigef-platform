import { Routes } from "@angular/router";
import { MainLayoutComponent } from "../../layouts/main-layout/main-layout.component";
import { NotificationsComponent } from "./pages/notifications/notifications.component";

export const NOTIFICATIONS_ROUTE: Routes = [
  {
    path: '',
    component: MainLayoutComponent,
    children: [
      {
        path: '',
        redirectTo: 'all',
        pathMatch: 'full'
      },
      {
        path: 'all',
        component: NotificationsComponent
      },
    ]
  }

];
