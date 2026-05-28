import { Routes } from '@angular/router';
import { MainLayoutComponent } from '../../layouts/main-layout/main-layout.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { ConceptsComponent } from './pages/concepts/concepts.component';
import { ConceptDetailComponent } from './pages/concept-detail/concept-detail.component';
import { DebtsComponent } from './pages/debts/debts.component';

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
      },
      {
        path: 'concepts',
        component: ConceptsComponent,
      },
      {
        path: 'concepts/:id',
        component: ConceptDetailComponent,
      },
      {
        path: 'debts',
        component: DebtsComponent
      },
    ],
  },
];
