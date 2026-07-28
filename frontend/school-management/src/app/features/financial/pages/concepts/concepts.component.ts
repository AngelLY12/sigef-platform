import { Paginated } from './../../../../core/utils/paginated-helper.utils';
import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { InfoCardComponent } from '../../../../shared/components/data-display/info-card/info-card.component';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { PaginatorComponent } from '../../../../shared/components/data-display/paginator/paginator.component';
import { PaymentConceptApiService } from '../../../../core/api/financial-staff/payment-concepts.api.service';
import { ConceptsListResponse } from '../../models/concepts/concepts-list.response.model';
import {
  ConceptsParams,
  createConceptsListParams,
} from '../../models/concepts/concepts-params.model';
import { ListController } from '../../../../core/utils/list-controller.utils';
import { Router } from '@angular/router';
import { ModalService } from '../../../../core/services/modal.service';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { FilterBarComponent } from '../../../../shared/components/features/filter-bar/filter-bar.component';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { CurrencyMXNPipe } from '../../../../shared/pipes/currency-mxn.pipe';
import { ConceptCreatedFormComponent } from '../../components/concepts/concept-created-form/concept-created-form.component';
import { DropdownComponent } from '../../../../shared/components/layout/dropdown/dropdown.component';
import { MenuItemComponent } from '../../../../shared/components/navigation/menu-item/menu-item.component';
import { PaymentConceptStatus } from '../../../../core/models/enums/payment-concepts-status.enum';
import { EMPTY, Observable } from 'rxjs';
import { enumToOptions } from '../../../../core/utils/enum-helper.utils';
import { FormsModule } from '@angular/forms';
import { FINANCIAL_NAVIGATION } from '../../../../core/navigation/financial-staff-navigation.config';
import { FolderTab } from '../../../../core/models/domain/folder-tabs-config.model';
import { FolderTabsComponent } from '../../../../shared/components/navigation/folder-tabs/folder-tabs.component';
import { EmptyStateComponent } from '../../../../shared/components/feedback/empty-state/empty-state.component';
import { ConceptActionsComponent } from '../../components/concepts/concept-actions/concept-actions.component';
import { ConceptCardComponent } from '../../components/concepts/concept-card/concept-card.component';
import { CONCEPT_LIST_TABS } from '../../config/financial.config';
import { ConceptsActionsService } from '../../services/concepts-actions.service';

@Component({
  selector: 'app-concepts',
  standalone: true,
  imports: [
    CommonModule,
    ButtonComponent,
    InfoCardComponent,
    PageLayoutComponent,
    PaginatorComponent,
    ButtonComponent,
    FormsModule,
    FolderTabsComponent,
    EmptyStateComponent,
    ConceptActionsComponent,
    ConceptCardComponent,
  ],
  templateUrl: './concepts.component.html',
  styleUrl: './concepts.component.scss',
})
export class ConceptsComponent implements OnInit {
  private conceptsService = inject(PaymentConceptApiService);
  private modalService = inject(ModalService);
  private conceptActions = inject(ConceptsActionsService);
  private router = inject(Router);
  private listController!: ListController<ConceptsParams>;
  paginatedConcepts: Paginated<ConceptsListResponse> | null = null;
  conceptsListParams: ConceptsParams = createConceptsListParams();
  conceptsState: LoadingState = 'idle';
  loadingConceptIds = new Set<number>();
  conceptStatus = enumToOptions(PaymentConceptStatus);
  conceptTabs: FolderTab[] = CONCEPT_LIST_TABS;
  activeConceptTab = 'all';

  ngOnInit(): void {
    this.listController = new ListController<ConceptsParams>(
      () => this.conceptsListParams,
      (params) => (this.conceptsListParams = params),
      () => this.loadConcepts(),
    );
    this.loadConcepts();
  }

  loadConcepts() {
    this.conceptsState = 'loading';
    this.conceptsService.getPaymentConcepts(this.conceptsListParams).subscribe({
      next: (res) => {
        this.conceptsState = 'success';
        this.paginatedConcepts = res;
      },
      error: () => {
        this.conceptsState = 'error';
      },
    });
  }

  onCreateConcept() {
    this.modalService.openCustom({
      title: 'Crear nuevo concepto de pago',
      component: ConceptCreatedFormComponent,
    });
  }

  onPageChange(newPage: number) {
    const updatedParams = QueryParamsHelper.changePage(
      this.conceptsListParams,
      newPage,
    );
    this.listController.update(updatedParams);
  }

  onPageSizeChange(newSize: number) {
    const updatedParams = QueryParamsHelper.changePageSize(
      this.conceptsListParams,
      newSize,
    );
    this.listController.update(updatedParams);
  }
  onResetFilters() {
    this.conceptsListParams = createConceptsListParams();
    this.loadConcepts();
  }

  onStatusFilterChange(tab: string) {
    this.activeConceptTab = tab;
    const updatedParams = QueryParamsHelper.changeStatus(
      this.conceptsListParams,
      (this.conceptsListParams.status =
        tab === 'all' ? null : (tab as PaymentConceptStatus)),
    );
    this.listController.update(updatedParams);
  }

  onRefreshData() {
    const updatedParams = QueryParamsHelper.refreshData(
      this.conceptsListParams,
    );
    this.listController.update(updatedParams);
  }

  isLoading(conceptId: number): boolean {
    return this.loadingConceptIds.has(conceptId);
  }

  setLoading(conceptId: number, value: boolean) {
    if (value) {
      this.loadingConceptIds.add(conceptId);
    } else {
      this.loadingConceptIds.delete(conceptId);
    }
  }

  onView(concept: ConceptsListResponse) {
    this.router.navigate(FINANCIAL_NAVIGATION.conceptDetail(concept.id));
  }

  onActivate(concept: ConceptsListResponse) {
    this.conceptActions.execute(concept, {
      forbiddenStatus: PaymentConceptStatus.ACTIVO,
      forbiddenMessage: 'Este concepto ya está activo',
      request: () => this.conceptsService.activateConcept(concept.id),
      onReload: () => this.loadConcepts(),
      setLoading: (loading) => this.setLoading(concept.id, loading),
    });
  }

  onFinalize(concept: ConceptsListResponse) {
    this.conceptActions.execute(concept, {
      forbiddenStatus: PaymentConceptStatus.FINALIZADO,
      forbiddenMessage: 'Este concepto ya está finalizado',
      request: () => this.conceptsService.finalizeConcept(concept.id),
      onReload: () => this.loadConcepts(),
      setLoading: (loading) => this.setLoading(concept.id, loading),
    });
  }

  onDesactivate(concept: ConceptsListResponse) {
    this.conceptActions.execute(concept, {
      forbiddenStatus: PaymentConceptStatus.DESACTIVADO,
      forbiddenMessage: 'Este concepto ya está desactivado',
      request: () => this.conceptsService.disableConcept(concept.id),
      onReload: () => this.loadConcepts(),
      setLoading: (loading) => this.setLoading(concept.id, loading),
    });
  }

  onDelete(concept: ConceptsListResponse) {
    this.conceptActions.delete(concept, () => this.loadConcepts());
  }

}
