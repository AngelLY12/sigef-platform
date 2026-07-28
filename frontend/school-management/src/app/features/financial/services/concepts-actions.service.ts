import { inject, Injectable } from '@angular/core';
import { EMPTY, Observable } from 'rxjs';
import { ConceptsListResponse } from '../models/concepts/concepts-list.response.model';
import { PaymentConceptStatus } from '../../../core/models/enums/payment-concepts-status.enum';
import { ModalService } from '../../../core/services/modal.service';
import { PaymentConceptApiService } from '../../../core/api/financial-staff/payment-concepts.api.service';

@Injectable({ providedIn: 'root' })
export class ConceptsActionsService {
  private conceptsService = inject(PaymentConceptApiService);
  private modalService = inject(ModalService);

  execute(
    concept: ConceptsListResponse,
    options: {
      forbiddenStatus?: PaymentConceptStatus;
      forbiddenMessage?: string;
      request: () => Observable<{ message: string }>;
      onReload: () => void;
      setLoading: (loading: boolean) => void;
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

    options.setLoading(true);

    options.request().subscribe({
      next: (res) => {
        options.setLoading(false);

        this.modalService.show({
          message: res.message,
          type: 'success',
          display: 'alert',
        });

        options.onReload();
      },
      error: () => {
        options.setLoading(false);
      },
    });
  }

  delete(concept: ConceptsListResponse, onReload: () => void) {
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
          switch (data.deleteConcept) {
            case 'TEMPORAL':
              return this.conceptsService.elimintaLogicalConcept(concept.id);

            case 'TOTAL':
              return this.conceptsService.eliminateConcept(concept.id);

            default:
              this.modalService.show({
                message: 'Debes escribir TEMPORAL o TOTAL',
                type: 'info',
                display: 'alert',
              });

              return EMPTY;
          }
        },

        onSuccess: (message: string) => {
          this.modalService.show({
            message,
            type: 'success',
            display: 'modal',
          });

          onReload();
        },
      },
      [concept],
    );
  }
}
