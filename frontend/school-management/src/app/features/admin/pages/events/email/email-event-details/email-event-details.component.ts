import { Component, inject, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../../../shared/components/layout/page-layout/page-layout.component';
import { LoadingState } from '../../../../../../core/models/types/loading-state.type';
import { ActivatedRoute } from '@angular/router';
import { AdminEmailEventsApiService } from '../../../../../../core/api/admin/events/admin-email-events.api.service';
import { EmailEventByIdResponse } from '../../../../models/response/events/email/email-event-by-id.response';
import { MetadataCardComponent } from '../../../../../../shared/components/data-display/metadata/metadata-card/metadata-card.component';
import { EmailEventMetadataComponent } from '../../../../components/events/email-events/metadata/email-event-metadata/email-event-metadata.component';
import { MetadataRowComponent } from '../../../../../../shared/components/data-display/metadata/metadata-row/metadata-row.component';
import { MetadataBadgeComponent } from '../../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { EmailEventStatus } from '../../../../models/request/events/email/email-event-status.enum';
import { CommonModule } from '@angular/common';
import { EventDetailsErrorComponent } from '../../../../components/events/event-details-error/event-details-error.component';
import { EventDetailsGridComponent } from '../../../../components/events/event-details-grid/event-details-grid.component';
import { EventDetailsHeaderComponent } from '../../../../components/events/event-details-header/event-details-header.component';
import { EventDetailsItemComponent } from '../../../../components/events/event-details-item/event-details-item.component';
import { EventDetailsSectionComponent } from '../../../../components/events/event-details-section/event-details-section.component';
import { EventDetailsTimelineComponent } from '../../../../components/events/event-details-timeline/event-details-timeline.component';
import { EventDetailsHeaderData } from '../../../../components/events/event-details-header/event-details-header.model';
import { EventTimelineItem } from '../../../../components/events/event-details-timeline/event-details-timeline.model';
import { ButtonComponent } from '../../../../../../shared/components/ui/button/button.component';

@Component({
  selector: 'app-email-event-details',
  standalone: true,
  imports: [
    CommonModule,
    PageLayoutComponent,
    EventDetailsErrorComponent,
    EventDetailsGridComponent,
    EventDetailsHeaderComponent,
    EventDetailsItemComponent,
    EventDetailsSectionComponent,
    EventDetailsTimelineComponent,
    EmailEventMetadataComponent,
  ],
  templateUrl: './email-event-details.component.html',
  styleUrl: './email-event-details.component.scss',
})
export class EmailEventDetailsComponent implements OnInit {
  detailsState: LoadingState = 'idle';
  private route = inject(ActivatedRoute);
  private emailEventApiService = inject(AdminEmailEventsApiService);
  eventId: number | null = null;
  emailEvent: EmailEventByIdResponse | null = null;
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
    this.emailEventApiService
      .getEmailEventById(userId, forceRefresh)
      .subscribe({
        next: (res) => {
          this.detailsState = 'success';
          this.emailEvent = res;
          this.eventHeader = {
            id: res.id,
            status: res.status,
            statusLabel: res.statusLabel,
            statusType: this.getStatusType(res.status),
            icon: 'mail',
            recipient: res.recipientEmail,
            context: [res.eventTypeLabel, res.sourceTypeLabel],
          };
          this.timeline = [
            {
              label: 'Creado',
              date: res.createdAt,
              status: 'success',
            },
            {
              label: 'Enviado',
              date: res.sentAt,
              status: res.sentAt ? 'success' : 'pending',
              badge: res.sentAt
                ? {
                    type: 'success',
                    text: 'Completado',
                  }
                : undefined,
            },
            {
              label: 'Entregado',
              date: res.deliveredAt,
              status: res.deliveredAt ? 'success' : 'pending',
              badge: res.deliveredAt
                ? {
                    type: 'success',
                    text: 'Entregado',
                  }
                : undefined,
            },
          ];
        },
        error: () => {
          this.detailsState = 'error';
        },
      });
  }

  getStatusType(
    status: EmailEventStatus,
  ): 'success' | 'warning' | 'error' | 'info' {
    switch (status) {
      case EmailEventStatus.DELIVERED:
        return 'success';

      case EmailEventStatus.SENT:
        return 'info';

      case EmailEventStatus.PENDING:
        return 'warning';

      case EmailEventStatus.FAILED:
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
