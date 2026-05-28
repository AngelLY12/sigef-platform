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
import { ConceptCreatedFormComponent } from '../../components/concept-created-form/concept-created-form.component';
import { DropdownComponent } from '../../../../shared/components/layout/dropdown/dropdown.component';
import { MenuItemComponent } from '../../../../shared/components/navigation/menu-item/menu-item.component';
import { PaymentConceptStatus } from '../../../../core/models/enums/payment-concepts-status.enum';
import { EMPTY, Observable } from 'rxjs';
import { SelectComponent } from '../../../../shared/components/form/select/select.component';
import { enumToOptions } from '../../../../core/utils/enum-helper.utils';
import { FormsModule } from '@angular/forms';
import { FINANCIAL_NAVIGATION } from '../../../../core/navigation/financial-staff-navigation.config';

@Component({
  selector: 'app-concepts',
  standalone: true,
  imports: [
    CommonModule,
    ButtonComponent,
    InfoCardComponent,
    FilterBarComponent,
    PageLayoutComponent,
    PaginatorComponent,
    CurrencyMXNPipe,
    ButtonComponent,
    DropdownComponent,
    MenuItemComponent,
    SelectComponent,
    FormsModule,
  ],
  templateUrl: './concepts.component.html',
  styleUrl: './concepts.component.scss',
})
export class ConceptsComponent implements OnInit {
  private conceptsService = inject(PaymentConceptApiService);
  private modalService = inject(ModalService);
  private router = inject(Router);
  private listController!: ListController<ConceptsParams>;
  paginatedConcepts: Paginated<ConceptsListResponse> | null = null;
  conceptsListParams: ConceptsParams = createConceptsListParams();
  conceptsState: LoadingState = 'idle';
  loadingConceptIds = new Set<number>();
  conceptStatus = enumToOptions(PaymentConceptStatus);

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

  onStatusFilterChange() {
    const updatedParams = QueryParamsHelper.changeStatus(
      this.conceptsListParams,
      this.conceptsListParams.status,
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
    this.handleConceptAction(concept, {
      forbiddenStatus: PaymentConceptStatus.ACTIVO,
      forbiddenMessage: 'Este concepto ya está activo',
      request: () => this.conceptsService.activateConcept(concept.id),
    });
  }
  onFinalize(concept: ConceptsListResponse) {
    this.handleConceptAction(concept, {
      forbiddenStatus: PaymentConceptStatus.FINALIZADO,
      forbiddenMessage: 'Este concepto ya está finalizado',
      request: () => this.conceptsService.finalizeConcept(concept.id),
    });
  }
  onDesactivate(concept: ConceptsListResponse) {
    this.handleConceptAction(concept, {
      forbiddenStatus: PaymentConceptStatus.DESACTIVADO,
      forbiddenMessage: 'Este concepto ya está desactivado',
      request: () => this.conceptsService.disableConcept(concept.id),
    });
  }
  onDelete(concept: ConceptsListResponse, dropdown: DropdownComponent) {
    dropdown.closeDropdown();
    this.modalService.openActions(
      {
        title: 'Eliminar concepto',
        entityName: 'concepto',
        description:
          'Escribe TEMPORAL si no quieres eliminar el concepto totalmente y TOTAL si quieres que la acción no se pueda deshacer.',
        fields: [
          {
            name: 'deleteConcept',
            type: 'input',
            label: 'Eliminación del concepto',
            placeHolder: 'TEMPORAL O TOTAL',
            fullWidth: true,
            isBulkOperation: false,
          },
        ],
        onSubmit: (data) => {
          const state = data.deleteConcept;
          if (state === 'TEMPORAL') {
            return this.conceptsService.elimintaLogicalConcept(concept.id);
          }

          if (state === 'TOTAL') {
            return this.conceptsService.eliminateConcept(concept.id);
          }

          this.modalService.show({
            message: 'Debes escribir TEMPORAL o TOTAL',
            type: 'info',
            display: 'alert',
          });

          return EMPTY;
        },
        onSuccess: (res: string) => {
          const message = res;

          this.modalService.show({
            message: `
                ${message}
              `,
            type: 'success',
            display: 'modal',
          });
          this.loadConcepts();
        },
      },
      [concept],
    );
  }

  private handleConceptAction(
    concept: ConceptsListResponse,
    options: {
      forbiddenStatus?: PaymentConceptStatus;
      forbiddenMessage?: string;
      request: () => Observable<{ message: string }>;
    },
  ) {
    if (options.forbiddenStatus && concept.status === options.forbiddenStatus) {
      this.modalService.show({
        message: options.forbiddenMessage ?? 'Acción no permitida',
        type: 'info',
        display: 'alert',
      });
      return;
    }

    this.setLoading(concept.id, true);

    options.request().subscribe({
      next: (res) => {
        this.setLoading(concept.id, false);

        this.modalService.show({
          message: res.message,
          type: 'success',
          display: 'alert',
        });

        this.loadConcepts();
      },
      error: () => {
        this.setLoading(concept.id, false);
      },
    });
  }
}
