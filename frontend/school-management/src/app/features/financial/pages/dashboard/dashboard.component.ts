import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { DashboardService } from '../../../../core/api/financial-staff/dashboard.api.service';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { TotalPending } from '../../models/dashboard/pendig-concept.response.model';
import { TotalStudents } from '../../models/dashboard/students-summary.response.model';
import { PaymentsData } from '../../models/dashboard/payments.response.model';
import { ConceptsHistoryItems } from '../../models/dashboard/concepts-history.response.model';
import { Paginated } from '../../../../core/utils/paginated-helper.utils';
import { catchError, forkJoin, of } from 'rxjs';
import { FormsModule } from '@angular/forms';
import { DashboardKpisComponent } from '../../components/dashboard/dashboard-kpis/dashboard-kpis.component';
import { DashboardChartsComponent } from '../../components/dashboard/dashboard-charts/dashboard-charts.component';
import { DashboardHistoryComponent } from '../../components/dashboard/dashboard-history/dashboard-history.component';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    PageLayoutComponent,
    FormsModule,
    DashboardKpisComponent,
    DashboardChartsComponent,
    DashboardHistoryComponent
  ],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss',
})
export class DashboardComponent implements OnInit {
  private dashboardService = inject(DashboardService);

  state: LoadingState = 'loading';

  pendingSummary: TotalPending | null = null;
  studentsSummary: TotalStudents | null = null;
  paymentsSummary: PaymentsData | null = null;
  conceptsHistory: Paginated<ConceptsHistoryItems> | null = null;

  ngOnInit(): void {
    this.loadDashboard();
  }

  loadDashboard() {
    this.state = 'loading';

    forkJoin({
      pending: this.dashboardService
        .getPending()
        .pipe(catchError(() => of(null))),
      students: this.dashboardService
        .getStudents()
        .pipe(catchError(() => of(null))),
      payments: this.dashboardService
        .getPayments()
        .pipe(catchError(() => of(null))),
      history: this.dashboardService
        .getConceptsHistory()
        .pipe(catchError(() => of(null))),
    }).subscribe({
      next: ({ pending, students, payments, history }) => {
        if (pending) this.pendingSummary = pending;
        if (students) this.studentsSummary = students;
        if (payments) this.paymentsSummary = payments;
        if (history) this.conceptsHistory = history;

        this.state = 'success';
      },
      error: () => (this.state = 'error'),
    });
  }
  refresh() {
    this.loadDashboard();
  }
  createPayout() {
    this.dashboardService.createPayout().subscribe(() => {
      this.refresh();
    });
  }
}
