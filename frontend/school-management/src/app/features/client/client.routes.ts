import { Routes } from '@angular/router';
import { MainLayoutComponent } from '../../layouts/main-layout/main-layout.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { PendingConceptsComponent } from './pages/pending-concepts/pending-concepts.component';
import { CardsComponent } from './pages/cards/cards.component';
import { PaymentHistoryComponent } from './pages/payment-history/payment-history.component';
import { PaymentDetailsComponent } from './pages/payment-details/payment-details.component';
import { ParentsComponent } from './pages/parents/parents.component';
import { ChildrenComponent } from './pages/children/children.component';
import { NAVIGATION } from '../../core/navigation/navigation.config';

export const CLIENT_ROUTES: Routes = [
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
        path: 'concepts',
        component: PendingConceptsComponent,
        data: {
          title: 'Conceptos pendientes',
          breadcrumb: 'Conceptos',
        },
      },
      {
        path: 'cards',
        component: CardsComponent,
        data: {
          title: 'Tarjetas',
          breadcrumb: 'Tarjetas',
        },
      },
      {
        path: 'payment/history',
        component: PaymentHistoryComponent,
        data: {
          title: 'Historial de pagos',
          breadcrumb: 'Historial de pagos',
        },
      },
      {
        path: 'payment/:id',
        component: PaymentDetailsComponent,
        data: {
          title: 'Detalle del pago',
          breadcrumb: 'Detalle',
          breadcrumbParam: {
            param: 'id',
            label: 'Pago',
          },
          breadcrumbParent: {
            label: 'Historial de pagos',
            url: NAVIGATION.client.paymentHistory,
          },
        },
      },
      {
        path: 'parents',
        component: ParentsComponent,
        data: {
          title: 'Padres',
          breadcrumb: 'Padres',
        },
      },
      {
        path: 'children',
        component: ChildrenComponent,
        data: {
          title: 'Hijos',
          breadcrumb: 'Hijos',
        },
      },
    ],
  },
];
