import { Routes } from '@angular/router';
import { AcceptInviteComponent } from './pages/accept-invite/accept-invite.component';
import { PublicLayoutComponent } from '../../layouts/public-layout/public-layout.component';

export const PARENTS_ROUTES: Routes = [
  {
    path: 'accept-invite',
    component: AcceptInviteComponent,
    data: {
      title: 'Invitación',
      breadcrumb: 'Invitación',
    },
  },
];
