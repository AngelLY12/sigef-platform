import { Routes } from '@angular/router';
import { MainLayoutComponent } from '../../layouts/main-layout/main-layout.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { ConceptsComponent } from './pages/concepts/concepts.component';
import { ConceptDetailComponent } from './pages/concept-detail/concept-detail.component';
import { DebtsComponent } from './pages/debts/debts.component';
import { PaymentsComponent } from './pages/payments/payments.component';
import { NAVIGATION } from '../../core/navigation/navigation.config';

export const FINANCIAL_ROUTES: Routes = [
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
        component: ConceptsComponent,
        data: {
          title: 'Conceptos de pago',
          breadcrumb: 'Conceptos',
        },
      },
      {
        path: 'concepts/:id',
        component: ConceptDetailComponent,
        data: {
          title: 'Detalle del concepto',
          breadcrumb: 'Detalle',
          breadcrumbParam: {
            param: 'id',
            label: 'Concepto',
          },
          breadcrumbParent: {
            label: 'Conceptos de pago',
            url: NAVIGATION.financial.concepts,
          },
        },
      },
      {
        path: 'debts',
        component: DebtsComponent,
        data: {
          title: 'Adeudos',
          breadcrumb: 'Adeudos',
        },
      },
      {
        path: 'payments',
        component: PaymentsComponent,
        data: {
          title: 'Pagos',
          breadcrumb: 'Pagos',
        },
      },
    ],
  },
];
