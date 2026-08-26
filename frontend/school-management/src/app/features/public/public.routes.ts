import { Routes } from '@angular/router';
import { NotFoundComponent } from './pages/not-found/not-found.component';
import { UnverifiedComponent } from './pages/unverified/unverified.component';
import { MaintenanceComponent } from './pages/maintenance/maintenance.component';
import { UnauthorizedComponent } from './pages/unauthorized/unauthorized.component';
import { RoleSelectorPageComponent } from './pages/role-selector-page/role-selector-page.component';
import { protectedGuard } from '../../core/guards/protected.guard';
import { CheckoutSuccessComponent } from './pages/checkout-success/checkout-success.component';
import { CheckoutCancelComponent } from './pages/checkout-cancel/checkout-cancel.component';
import { EmailVerificationComponent } from './pages/email-verification/email-verification.component';

export const PUBLIC_ROUTES: Routes = [
  {
    path: '404',
    component: NotFoundComponent,
    data: {
      title: 'Página no encontrada',
    },
  },
  {
    path: 'unverified',
    component: UnverifiedComponent,
    data: {
      title: 'Cuenta no verificada',
    },
  },
  {
    path: 'maintenance',
    component: MaintenanceComponent,
    data: {
      title: 'Mantenimiento',
    },
  },
  {
    path: 'unauthorized',
    component: UnauthorizedComponent,
    data: {
      title: 'Acceso no autorizado',
    },
  },
  {
    path: 'selector',
    component: RoleSelectorPageComponent,
    canActivate: [protectedGuard],
    data: {
      title: 'Seleccionar rol',
    },
  },
  {
    path: 'checkout/success',
    component: CheckoutSuccessComponent,
    data: {
      title: 'Pago exitoso',
    },
  },
  {
    path: 'checkout/cancel',
    component: CheckoutCancelComponent,
    data: {
      title: 'Pago cancelado',
    },
  },
  {
    path: 'email-verification',
    component: EmailVerificationComponent,
    data: {
      title: 'Verificación de correo',
    },
  },
  { path: '**', redirectTo: '/common/404' },
];
