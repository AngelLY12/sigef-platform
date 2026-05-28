import { Routes } from "@angular/router";
import { LoginComponent } from "./pages/login/login.component";
import { RegisterComponent } from "./pages/register/register.component";
import { ForgotPasswordComponent } from "./pages/forgot-password/forgot-password.component";
import { ResetPasswordComponent } from "./pages/reset-password/reset-password.component";
import { authGuard } from "../../core/guards/auth.guard";

export const AUTH_ROUTES: Routes = [

  {
    path: 'login',
    component: LoginComponent,
    canActivate: [authGuard],
    data: { title: 'Login'}
  },
  {
    path: 'register',
    component: RegisterComponent,
  },
  {
    path: 'forgot-password',
    component: ForgotPasswordComponent,
  },
  {
    path: 'reset-password',
    component: ResetPasswordComponent,

  },
  {
    path: '',
    redirectTo: 'login',
    pathMatch: 'full'
  },
];
