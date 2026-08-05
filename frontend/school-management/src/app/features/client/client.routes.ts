import { Routes } from '@angular/router';
import { MainLayoutComponent } from '../../layouts/main-layout/main-layout.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { PendingConceptsComponent } from './pages/pending-concepts/pending-concepts.component';
import { CardsComponent } from './pages/cards/cards.component';
import { PaymentHistoryComponent } from './pages/payment-history/payment-history.component';
import { PaymentDetailsComponent } from './pages/payment-details/payment-details.component';
import { ParentsComponent } from './pages/parents/parents.component';
import { ChildrenComponent } from './pages/children/children.component';

export const CLIENT_ROUTES: Routes = [
  {
    path: '',
    component: MainLayoutComponent,
    children: [
      {
        path: '',
        redirectTo: 'dashboard',
        pathMatch: 'full',
        data: { title: 'Dashboard'}
      },
      {
        path: 'dashboard',
        component: DashboardComponent,
      },
      {
        path: 'concepts',
        component: PendingConceptsComponent
      },
      {
        path: 'cards',
        component: CardsComponent
      },
      {
        path: 'payment/history',
        component: PaymentHistoryComponent
      },
      {
        path: 'payment/:id',
        component: PaymentDetailsComponent
      },
      {
        path: 'parents',
        component: ParentsComponent
      },
      {
        path: 'children',
        component: ChildrenComponent,

      },
    ],
  },
];
