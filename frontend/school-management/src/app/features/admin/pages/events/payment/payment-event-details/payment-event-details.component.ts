import { Component, inject, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../../../shared/components/layout/page-layout/page-layout.component';
import { EventDetailsErrorComponent } from '../../../../components/events/event-details-error/event-details-error.component';
import { EventDetailsHeaderComponent } from '../../../../components/events/event-details-header/event-details-header.component';
import { EventDetailsItemComponent } from '../../../../components/events/event-details-item/event-details-item.component';
import { EventDetailsGridComponent } from '../../../../components/events/event-details-grid/event-details-grid.component';
import { EventDetailsSectionComponent } from '../../../../components/events/event-details-section/event-details-section.component';
import { EventDetailsTimelineComponent } from '../../../../components/events/event-details-timeline/event-details-timeline.component';
import { LoadingState } from '../../../../../../core/models/types/loading-state.type';
import { ActivatedRoute } from '@angular/router';
import { AdminPaymentEventsApiService } from '../../../../../../core/api/admin/events/admin-payment-events.api.service';
import { PaymentEventMetadataComponent } from '../../../../components/events/payment-events/metadata/payment-event-metadata/payment-event-metadata.component';
import { PaymentEventByIdResponse } from '../../../../models/response/events/payment/payment-event-by-id.response';
import { EventDetailsHeaderData } from '../../../../components/events/event-details-header/event-details-header.model';
import { EventTimelineItem } from '../../../../components/events/event-details-timeline/event-details-timeline.model';
import { PaymentStatus } from '../../../../../../core/models/enums/payment-status.enum';
import { CommonModule } from '@angular/common';
import { CurrencyMXNPipe } from '../../../../../../shared/pipes/currency-mxn.pipe';

@Component({
  selector: 'app-payment-event-details',
  imports: [
    PageLayoutComponent,
    EventDetailsErrorComponent,
    EventDetailsHeaderComponent,
    EventDetailsItemComponent,
    EventDetailsGridComponent,
    EventDetailsSectionComponent,
    EventDetailsTimelineComponent,
    PaymentEventMetadataComponent,
    CommonModule,
    CurrencyMXNPipe
  ],
  templateUrl: './payment-event-details.component.html',
  styleUrl: './payment-event-details.component.scss',
})
export class PaymentEventDetailsComponent implements OnInit {
  detailsState: LoadingState = 'idle';
  private route = inject(ActivatedRoute);
  private paymentEventApiService = inject(AdminPaymentEventsApiService);
  eventId: number | null = null;
  forceRefresh: boolean = false;
  paymentEvent: PaymentEventByIdResponse | null = null;
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
      .getPaymentEventById(userId, forceRefresh)
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
            context: [res.eventTypeLabel],
          };
          this.timeline = [
            {
              label: 'Creado',
              date: res.createdAt,
              status: 'success',
            },
            {
              label: res.errorMessage
                ? 'Procesamiento fallido'
                : res.processed
                  ? 'Procesado'
                  : 'En espera de procesamiento',
              date: res.processedAt,
              status: res.errorMessage
                ? 'error'
                : res.processed
                  ? 'success'
                  : 'pending',
              badge: res.errorMessage
                ? {
                    type: 'error',
                    text: 'Fallido',
                  }
                : res.processed
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
    status: PaymentStatus | null,
  ): 'success' | 'warning' | 'error' | 'info' {
    if (!status) {
      return 'info';
    }
    switch (status) {
      case PaymentStatus.PAID:
      case PaymentStatus.SUCCEEDED:
      case PaymentStatus.OVERPAID:
        return 'success';

      case PaymentStatus.PENDING:
      case PaymentStatus.REQUIRES_ACTION:
        return 'info';

      case PaymentStatus.UNDERPAID:
        return 'warning';

      case PaymentStatus.FAILED:
      case PaymentStatus.UNPAID:
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
