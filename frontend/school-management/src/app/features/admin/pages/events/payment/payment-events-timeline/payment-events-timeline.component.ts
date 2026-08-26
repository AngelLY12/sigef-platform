import { Component, inject, Input, OnInit } from '@angular/core';
import { EventDetailsTimelineComponent } from '../../../../components/events/event-details-timeline/event-details-timeline.component';
import { AdminPaymentEventsApiService } from '../../../../../../core/api/admin/events/admin-payment-events.api.service';
import { PaymentEventResponse } from '../../../../models/response/events/payment/payment-event.response';
import { LoadingState } from '../../../../../../core/models/types/loading-state.type';
import { EventTimelineItem } from '../../../../components/events/event-details-timeline/event-details-timeline.model';
import { EventDetailsSectionComponent } from '../../../../components/events/event-details-section/event-details-section.component';

@Component({
  selector: 'app-payment-events-timeline',
  imports: [
    EventDetailsTimelineComponent,
    EventDetailsSectionComponent,
  ],
  templateUrl: './payment-events-timeline.component.html',
  styleUrl: './payment-events-timeline.component.scss',
})
export class PaymentEventsTimelineComponent implements OnInit {
  private paymenEventApiService = inject(AdminPaymentEventsApiService);
  @Input({ required: true }) paymentId! : number;
  eventState: LoadingState = 'idle';
  events: PaymentEventResponse[] = [];

  ngOnInit(): void {
    this.loadTimeline(this.paymentId);
  }

  loadTimeline(paymentId: number, forceRefresh: boolean = false): void {
    this.eventState = 'loading';
    this.paymenEventApiService
      .getPaymentEventsTimeline(paymentId, forceRefresh)
      .subscribe({
        next: (res) => {
          this.eventState = 'success';
          this.events = res;
        },
        error: () => {
          this.eventState = 'error';
        },
      });
  }


  onRefreshData() {
    this.loadTimeline(this.paymentId!, true);
  }

  get timelineItems(): EventTimelineItem[] {
    if (!this.events) {
      return [];
    }

    return this.events.map((event) => ({
      label: event.eventType,
      date: event.createdAt,
      status: event.processed ? 'success' : 'error',
      badge: {
        type: event.processed ? 'success' : 'error',
        text: event.processed ? 'Procesado' : 'No procesado',
      },
      description: event.conceptName ?? undefined,
    }));
  }
}
