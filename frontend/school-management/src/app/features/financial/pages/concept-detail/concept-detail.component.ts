import { CommonModule } from '@angular/common';
import { Component, HostListener, inject, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { PaymentConceptApiService } from '../../../../core/api/financial-staff/payment-concepts.api.service';
import { ActivatedRoute } from '@angular/router';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { ConceptDetailResponse } from '../../models/concepts/concept-detail-response.model';
import { InfoCardComponent } from '../../../../shared/components/data-display/info-card/info-card.component';
import { CurrencyMXNPipe } from '../../../../shared/pipes/currency-mxn.pipe';
import { InfoCardItemComponent } from '../../../../shared/components/data-display/info-card-item/info-card-item.component';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { ExpandableSectionComponent } from '../../../../shared/components/layout/expandable-section/expandable-section.component';
import { catchError, forkJoin, of } from 'rxjs';
import { ConceptRelationsResponse } from '../../models/concepts/concept-relations-response.model';
import { CareersService } from '../../../../core/api/careers.api.service';
import { CareersResponse } from '../../../../core/models/responses/careers-response.model';
import { ModalService } from '../../../../core/services/modal.service';
import { ConceptEditFormComponent } from '../../components/concept-edit-form/concept-edit-form.component';

@Component({
  selector: 'app-concept-detail',
  imports: [
    CommonModule,
    PageLayoutComponent,
    InfoCardComponent,
    InfoCardItemComponent,
    ButtonComponent,
  ],
  providers: [CurrencyMXNPipe],
  templateUrl: './concept-detail.component.html',
  styleUrl: './concept-detail.component.scss',
})
export class ConceptDetailComponent implements OnInit {
  private conceptsService = inject(PaymentConceptApiService);
  private careersService = inject(CareersService);
  private modalService = inject(ModalService);
  private route = inject(ActivatedRoute);
  private currencyMXNPipe = inject(CurrencyMXNPipe);
  conceptId: number | null = null;
  concept: ConceptDetailResponse | null = null;
  relations: ConceptRelationsResponse | null = null;
  careers: CareersResponse[] | null = null;
  state: LoadingState = 'idle';
  detailSections: any[] = [];
  activeSection = 'general';

  isMobile = window.innerWidth <= 768;
  @HostListener('window:resize')
  onResize() {
    const isNowMobile = window.innerWidth <= 768;

    if (this.isMobile !== isNowMobile) {
      this.isMobile = isNowMobile;
    }
  }

  ngOnInit(): void {
    this.conceptId = this.loadConceptIdFromRoute();
    if (!this.conceptId) return;

    this.loadConceptData(this.conceptId);
  }

  loadConceptData(id: number) {
    forkJoin({
      concept: this.conceptsService
        .conceptDetail(id)
        .pipe(catchError(() => of(null))),
      relations: this.conceptsService
        .conceptRelations(id)
        .pipe(catchError(() => of(null))),
      careers: this.careersService
        .getCareers()
        .pipe(catchError(() => of(null))),
    }).subscribe({
      next: ({ concept, relations, careers }) => {
        this.state = 'success';
        this.concept = concept;
        this.relations = relations;
        this.careers = careers;
        this.buildDetailSections();
      },
      error: (err) => {
        this.state = 'error';
      },
    });
  }

  loadConceptIdFromRoute(): number | null {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (!idParam) {
      this.state = 'error';
      return null;
    }
    return +idParam;
  }

  private buildDetailSections() {
    this.detailSections = [
      {
        key: 'general',
        title: 'Información general',
        icon: 'info',
        editable: true,
        items: [
          {
            icon: 'badge',
            label: 'ID',
            value: this.concept?.id,
          },
          {
            icon: 'title',
            label: 'Nombre',
            value: this.concept?.concept_name,
          },
          {
            icon: 'description',
            label: 'Descripción',
            value: this.concept?.description || 'Sin descripción',
          },
          {
            icon: 'attach_money',
            label: 'Monto',
            value: this.currencyMXNPipe.transform(this.concept?.amount),
          },
          {
            icon: 'groups',
            label: 'Aplica a',
            value: this.concept?.applies_to?.toUpperCase(),
          },
          {
            icon: 'flag',
            label: 'Estado',
            value: this.concept?.status?.toUpperCase(),
          },
        ],
      },

      {
        key: 'dates',
        title: 'Fechas',
        icon: 'calendar_month',
        editable: true,
        items: [
          {
            icon: 'event',
            label: 'Fecha inicio',
            value: this.concept?.start_date,
          },
          {
            icon: 'event_busy',
            label: 'Fecha fin',
            value: this.concept?.end_date || 'Sin fecha fin',
          },
          {
            icon: 'schedule',
            label: 'Creado',
            value: this.concept?.created_at_human,
          },
          {
            icon: 'update',
            label: 'Última actualización',
            value: this.concept?.updated_at_human,
          },
          {
            icon: 'delete',
            label: 'Eliminado',
            value: this.concept?.deleted_at_human || 'No eliminado',
          },
        ],
      },

      {
        key: 'expiration',
        title: 'Expiración',
        icon: 'schedule',
        items: [
          {
            icon: 'timer',
            label: 'Expiración',
            value: this.concept?.expiration_human || 'N/A',
          },
          {
            icon: 'info',
            label: 'Texto',
            value: this.concept?.expiration_info.text || 'N/A',
          },
          {
            icon: 'calendar_today',
            label: 'Días restantes',
            value: this.concept?.expiration_info.days || 'N/A',
          },
          {
            icon: 'priority_high',
            label: 'Urgencia',
            value: this.concept?.expiration_info.urgency || 'N/A',
          },
          {
            icon: 'warning',
            label: 'Expirado',
            value: this.concept?.expiration_info.is_expired ? 'Sí' : 'No',
          },
          {
            icon: 'today',
            label: 'Expira hoy',
            value: this.concept?.expiration_info.is_today ? 'Sí' : 'No',
          },
          {
            icon: 'date_range',
            label: 'Fecha formateada',
            value: this.concept?.expiration_info.date_formatted || 'N/A',
          },
          {
            icon: 'calendar_view_day',
            label: 'Fecha corta',
            value: this.concept?.expiration_info.date_short || 'N/A',
          },
        ],
      },

      {
        key: 'delete',
        title: 'Eliminación',
        icon: 'delete',
        items: [
          {
            icon: 'hourglass_bottom',
            label: 'Días hasta eliminación',
            value: this.concept?.days_until_deletion || 'N/A',
          },
          {
            icon: 'delete_forever',
            label: 'Fecha eliminación',
            value: this.concept?.deleted_at || 'N/A',
          },
        ],
      },
      {
        key: 'relations',
        title: 'Relaciones',
        icon: 'account_tree',
        editable: true,
        items: [
          {
            icon: 'badge',
            label: 'ID del concepto',
            value: this.relations?.id,
          },
          {
            icon: 'title',
            label: 'Nombre del concepto',
            value: this.relations?.concept_name,
          },
          {
            icon: 'groups',
            label: 'Aplica a',
            value: this.relations?.applies_to?.toUpperCase(),
          },
          {
            icon: 'person',
            label: 'Usuarios',
            value: this.relations?.users?.length
              ? this.relations.users.join(', ')
              : 'Sin usuarios',
          },
          {
            icon: 'school',
            label: 'Carreras',
            value: this.relations?.careers?.length
              ? this.relations.careers
                  .map(
                    (careerId) =>
                      this.careers?.find((c) => c.id === careerId)?.career_name,
                  )
                  .filter(Boolean)
                  .join(', ')
              : 'Sin carreras',
          },
          {
            icon: 'calendar_view_month',
            label: 'Semestres',
            value: this.relations?.semesters?.length
              ? this.relations.semesters.join(', ')
              : 'Sin semestres',
          },
          {
            icon: 'person_off',
            label: 'Usuarios excluidos',
            value: this.relations?.exceptionUsers?.length
              ? this.relations.exceptionUsers.join(', ')
              : 'Sin excepciones',
          },
          {
            icon: 'sell',
            label: 'Tags de aspirantes',
            value: this.relations?.applicantTags?.length
              ? this.relations.applicantTags.join(', ')
              : 'Sin tags',
          },
        ],
      },
    ];
  }

  openEdit() {
    this.modalService.openCustom({
      title: 'Edita el concepto de pago',
      component: ConceptEditFormComponent,
      data: {
        concept: this.concept,
        relations: this.relations,
        careers: this.careers,
      },
      onClose: (updated) => {
        if(updated) {
          this.loadConceptData(this.conceptId!)
        }
      }
    })
  }

}
