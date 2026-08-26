import { Component, inject, Input } from '@angular/core';
import { EventTimelineItem, TimelineStatus } from '../../../../components/events/event-details-timeline/event-details-timeline.model';
import { AdminReconciliationEventsApiService } from '../../../../../../core/api/admin/events/admin-reconciliation-events.api.service';
import { LoadingState } from '../../../../../../core/models/types/loading-state.type';
import { ReconcileEventResponse } from '../../../../models/response/events/reconciliation/reconcile-event.response';
import { MetadataBadgeType } from '../../../../components/events/event-details-header/event-details-header.model';
import { EventDetailsSectionComponent } from '../../../../components/events/event-details-section/event-details-section.component';
import { EventDetailsTimelineComponent } from '../../../../components/events/event-details-timeline/event-details-timeline.component';

@Component({
  selector: 'app-reconciliation-events-timeline',
  imports: [EventDetailsSectionComponent, EventDetailsTimelineComponent],
  templateUrl: './reconciliation-events-timeline.component.html',
  styleUrl: './reconciliation-events-timeline.component.scss',
})
export class ReconciliationEventsTimelineComponent {
  private paymenEventApiService = inject(AdminReconciliationEventsApiService);
  @Input({ required: true }) paymentId!: number;
  eventState: LoadingState = 'idle';
  events: ReconcileEventResponse[] = [];

  ngOnInit(): void {
    this.loadTimeline(this.paymentId);
  }

  loadTimeline(paymentId: number, forceRefresh: boolean = false): void {
    this.eventState = 'loading';
    this.paymenEventApiService
      .getReconciliationTimeline(paymentId, forceRefresh)
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
    return this.events.map((event) => ({
      label: event.conceptName ?? 'Sin concepto',
      date: event.createdAt,
      status: this.getTimelineStatus(event.status),
      badge: {
        type: this.getStatusType(event.status),
        text: event.status,
      },
      description: event.sourceId
        ? `${event.sourceType ?? 'Fuente desconocida'} · ${event.sourceId}`
        : (event.sourceType ?? undefined),
    }));
  }

  private getTimelineStatus(status: string): TimelineStatus {
  switch (status) {
    case 'Completada':
      return 'success';

    case 'Fallida':
      return 'error';

    default:
      return 'pending';
  }
}

private getStatusType(status: string): MetadataBadgeType {
  switch (status) {
    case 'Completada':
      return 'success';

    case 'Fallida':
      return 'error';

    default:
      return 'warning';
  }
}

}
