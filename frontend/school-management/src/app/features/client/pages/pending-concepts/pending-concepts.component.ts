import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { PendingConcepts } from '../../../../core/api/students/pending-concepts.api.service';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { PendingConceptsResponse } from '../../models/pending-concepts/pending-concepts-response.model';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { forkJoin } from 'rxjs';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { PendingConceptsListComponent } from '../../components/pending-concepts-list/pending-concepts-list.component';
import { ListController } from '../../../../core/utils/list-controller.utils';
import {
  createPendingConceptsListParams,
  PendingConceptsParams,
} from '../../models/pending-concepts/pending-concepts-params.model';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { FolderTabsComponent } from '../../../../shared/components/navigation/folder-tabs/folder-tabs.component';
import { FolderTab } from '../../../../core/models/domain/folder-tabs-config.model';
import { PENDING_CONCEPTS_TABS } from '../../config/client.config';

@Component({
  selector: 'app-pending-concepts',
  standalone: true,
  imports: [
    CommonModule,
    PageLayoutComponent,
    PendingConceptsListComponent,
    FolderTabsComponent,
  ],
  templateUrl: './pending-concepts.component.html',
  styleUrl: './pending-concepts.component.scss',
})
export class PendingConceptsComponent implements OnInit {
  private pendingConcepts = inject(PendingConcepts);
  loading: LoadingState = 'idle';
  pending: PendingConceptsResponse[] | null = null;
  overdue: PendingConceptsResponse[] | null = null;
  listController!: ListController<PendingConceptsParams>;
  conceptsParams = createPendingConceptsListParams();
  loadingConceptId: number | null = null;
  activeConceptTab = 'pending';
  conceptsTabs: FolderTab[] = PENDING_CONCEPTS_TABS;

  ngOnInit(): void {
    this.listController = new ListController<PendingConceptsParams>(
      () => this.conceptsParams,
      (params) => (this.conceptsParams = params),
      () => this.loadConcepts(),
    );
    this.loadConcepts();
  }

  loadConcepts() {
    this.loading = 'loading';
    forkJoin({
      pending: this.pendingConcepts.getPendingConcepts(),
      overdue: this.pendingConcepts.getOverdueConcepts(),
    }).subscribe({
      next: ({ pending, overdue }) => {
        this.pending = pending;
        this.overdue = overdue;
        this.loading = 'success';
      },
      error: () => {
        this.loading = 'error';
      },
    });
  }

  onPay(concept_id: number) {
    this.loadingConceptId = concept_id;
    this.pendingConcepts.payConcept(concept_id).subscribe({
      next: (url) => {
        window.open(url, '_blank', 'noopener,noreferrer');
      },
      error: () => {
        this.loadingConceptId = null;
      },
      complete: () => {
        this.loadingConceptId = null;
      },
    });
  }

  onRefreshData() {
    const updatedParams = QueryParamsHelper.refreshData(this.conceptsParams);
    this.listController.update(updatedParams);
  }

}
