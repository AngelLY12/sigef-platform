import { Component, inject, OnInit } from '@angular/core';
import { EventDetailsErrorComponent } from '../../../../components/events/event-details-error/event-details-error.component';
import { EventDetailsGridComponent } from '../../../../components/events/event-details-grid/event-details-grid.component';
import { EventDetailsHeaderComponent } from '../../../../components/events/event-details-header/event-details-header.component';
import { EventDetailsItemComponent } from '../../../../components/events/event-details-item/event-details-item.component';
import { EventDetailsSectionComponent } from '../../../../components/events/event-details-section/event-details-section.component';
import { EventDetailsTimelineComponent } from '../../../../components/events/event-details-timeline/event-details-timeline.component';
import { LoadingState } from '../../../../../../core/models/types/loading-state.type';
import { ActivatedRoute } from '@angular/router';
import { AdminReconciliationEventsApiService } from '../../../../../../core/api/admin/events/admin-reconciliation-events.api.service';
import { ReconcileEventByIdResponse } from '../../../../models/response/events/reconciliation/reconcile-event-by-id.response';
import { EventDetailsHeaderData } from '../../../../components/events/event-details-header/event-details-header.model';
import { EventTimelineItem } from '../../../../components/events/event-details-timeline/event-details-timeline.model';
import { ReconciliationEventStatus } from '../../../../models/request/events/reconciliation/reconciliation-event-status.enum';
import { ReconciliationEventMetadataComponent } from '../../../../components/events/reconciliation-events/metadata/reconciliation-event-metadata/reconciliation-event-metadata.component';
import { PageLayoutComponent } from '../../../../../../shared/components/layout/page-layout/page-layout.component';
import { CurrencyMXNPipe } from '../../../../../../shared/pipes/currency-mxn.pipe';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-reconciliation-event-details',
  imports: [
    PageLayoutComponent,
    EventDetailsErrorComponent,
    EventDetailsGridComponent,
    EventDetailsHeaderComponent,
    EventDetailsItemComponent,
    EventDetailsSectionComponent,
    EventDetailsTimelineComponent,
    ReconciliationEventMetadataComponent,
    CommonModule,
  ],
  templateUrl: './reconciliation-event-details.component.html',
  styleUrl: './reconciliation-event-details.component.scss',
})
export class ReconciliationEventDetailsComponent implements OnInit {
  detailsState: LoadingState = 'idle';
  private route = inject(ActivatedRoute);
  private paymentEventApiService = inject(AdminReconciliationEventsApiService);
  eventId: number | null = null;
  forceRefresh: boolean = false;
  paymentEvent: ReconcileEventByIdResponse | null = null;
  eventHeader!: EventDetailsHeaderData;
  timeline!: EventTimelineItem[];

  ngOnInit(): void {
    this.eventId = this.loadEventIdFromRoute();
    if (!this.eventId) return;
    this.loadEvent(this.eventId);
  }

  loadEventIdFromRoute(): number | null {
    const idParam = this.route.snapshot.paramMap.get('eventId');
    if (!idParam) {
      this.detailsState = 'error';
      return null;
    }
    return +idParam;
  }

  loadEvent(userId: number, forceRefresh: boolean = false) {
    this.detailsState = 'loading';
    this.paymentEventApiService
      .getReconciliationEventById(userId, forceRefresh)
      .subscribe({
        next: (res) => {
          this.detailsState = 'success';
          this.paymentEvent = res;
          this.eventHeader = {
            id: res.id,
            status: res.status ?? 'Sin estatus',
            statusLabel: res.statusLabel,
            statusType: this.getStatusType(res.status),
            icon: 'mail',
            context: [res.sourceTypeLabel, res.outcomeLabel ?? 'Desconocido'],
          };
          this.timeline = [
            {
              label: 'Creado',
              date: res.createdAt,
              status: 'success',
            },
            {
              label: 'Procesamiento iniciado',
              date: res.startedAt,
              status: res.failedAt
                ? 'error'
                : res.completedAt
                  ? 'success'
                  : res.startedAt
                    ? 'success'
                    : 'pending',
              badge: res.failedAt
                ? {
                    type: 'error',
                    text: 'Fallido',
                  }
                : res.completedAt
                  ? {
                      type: 'success',
                      text: 'Completado',
                    }
                  : res.startedAt
                    ? {
                        type: 'info',
                        text: 'En proceso',
                      }
                    : undefined,
            },
            {
              label: res.failedAt
                ? 'Reconciliación fallida'
                : res.completedAt
                  ? 'Reconciliación completada'
                  : 'En espera de procesamiento',
              date: res.failedAt ?? res.completedAt,
              status: res.failedAt
                ? 'error'
                : res.completedAt
                  ? 'success'
                  : 'pending',
              badge: res.failedAt
                ? {
                    type: 'error',
                    text: 'Fallido',
                  }
                : res.completedAt
                  ? {
                      type: 'success',
                      text: 'Completado',
                    }
                  : {
                      type: 'warning',
                      text: 'Pendiente',
                    },
              description: res.errorMessage ?? undefined,
            },
          ];
        },
        error: () => {
          this.detailsState = 'error';
        },
      });
  }

  getStatusType(
    status: ReconciliationEventStatus | null,
  ): 'success' | 'warning' | 'error' | 'info' {
    if (!status) {
      return 'info';
    }
    switch (status) {
      case ReconciliationEventStatus.COMPLETED:
        return 'success';

      case ReconciliationEventStatus.PENDING:
        return 'info';

      case ReconciliationEventStatus.FAILED:
        return 'error';

      default:
        return 'info';
    }
  }

  onRefreshData() {
    if (!this.eventId) return;
    this.loadEvent(this.eventId, true);
  }
}
