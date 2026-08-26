import { Routes } from '@angular/router';
import { EmailEventDetailsComponent } from './pages/events/email/email-event-details/email-event-details.component';
import { EmailEventsHistoryComponent } from './pages/events/email/email-events-history/email-events-history.component';
import { EmailEventsComponent } from './pages/events/email/email-events/email-events.component';
import { PaymentEventDetailsComponent } from './pages/events/payment/payment-event-details/payment-event-details.component';
import { PaymentEventsTimelineComponent } from './pages/events/payment/payment-events-timeline/payment-events-timeline.component';
import { PaymentEventsComponent } from './pages/events/payment/payment-events/payment-events.component';
import { ReconciliationEventDetailsComponent } from './pages/events/reconciliation/reconciliation-event-details/reconciliation-event-details.component';
import { ReconciliationEventsTimelineComponent } from './pages/events/reconciliation/reconciliation-events-timeline/reconciliation-events-timeline.component';
import { ReconciliationEventsComponent } from './pages/events/reconciliation/reconciliation-events/reconciliation-events.component';
import { NAVIGATION } from '../../core/navigation/navigation.config';

export const ADMIN_EVENTS_ROUTES: Routes = [
  {
    path: 'email-events',
    component: EmailEventsComponent,
    data: {
      title: 'Eventos de correo',
      breadcrumb: 'Eventos de correo',
      breadcrumbParent: {
        label: 'Eventos',
      },
    },
  },

  {
    path: 'email-events/history/:userId',
    component: EmailEventsHistoryComponent,
    data: {
      title: 'Historial de eventos de correo',
      breadcrumb: 'Historial',
      breadcrumbParam: {
        param: 'userId',
        label: 'Usuario',
      },
      breadcrumbParent: [
        {
          label: 'Eventos',
        },
        {
          label: 'Eventos de correo',
          url: NAVIGATION.admin.emailEvents,
        },
      ],
    },
  },
  {
    path: 'email-events/:eventId',
    component: EmailEventDetailsComponent,
    data: {
      title: 'Evento de correo',
      breadcrumb: 'Detalle',
      breadcrumbParam: {
        param: 'eventId',
        label: 'Evento',
      },
      breadcrumbParent: [
        {
          label: 'Eventos',
        },
        {
          label: 'Eventos de correo',
          url: NAVIGATION.admin.emailEvents,
        },
      ],
    },
  },
  // Payment Events
  {
    path: 'payment-events',
    component: PaymentEventsComponent,
    data: {
      title: 'Eventos de pago',
      breadcrumb: 'Eventos de pago',
      breadcrumbParent: {
        label: 'Eventos',
      },
    },
  },
  {
    path: 'payment-events/timeline/:paymentId',
    component: PaymentEventsTimelineComponent,
    data: {
      title: 'Línea de tiempo del pago',
      breadcrumb: 'Línea de tiempo',
      breadcrumbParam: {
        param: 'paymentId',
        label: 'Pago',
      },
      breadcrumbParent: [
        {
          label: 'Eventos',
        },
        {
          label: 'Eventos de pago',
          url: NAVIGATION.admin.paymentEvents,
        },
      ],
    },
  },
  {
    path: 'payment-events/:eventId',
    component: PaymentEventDetailsComponent,
    data: {
      title: 'Evento de pago',
      breadcrumb: 'Detalle',
      breadcrumbParam: {
        param: 'eventId',
        label: 'Evento',
      },
      breadcrumbParent: [
        {
          label: 'Eventos',
        },
        {
          label: 'Eventos de pago',
          url: NAVIGATION.admin.paymentEvents,
        },
      ],
    },
  },

  // Reconciliation Events
  {
    path: 'reconciliation-events',
    component: ReconciliationEventsComponent,
    data: {
      title: 'Eventos de conciliación',
      breadcrumb: 'Eventos de conciliación',
      breadcrumbParent: {
        label: 'Eventos',
      },
    },
  },
  {
    path: 'reconciliation-events/timeline/:paymentId',
    component: ReconciliationEventsTimelineComponent,
    data: {
      title: 'Línea de tiempo de conciliación',
      breadcrumb: 'Línea de tiempo',
      breadcrumbParam: {
        param: 'paymentId',
        label: 'Pago',
      },
      breadcrumbParent: [
        {
          label: 'Eventos',
        },
        {
          label: 'Eventos de conciliación',
          url: NAVIGATION.admin.reconciliationEvents,
        },
      ],
    },
  },
  {
    path: 'reconciliation-events/:eventId',
    component: ReconciliationEventDetailsComponent,
    data: {
      title: 'Evento de conciliación',
      breadcrumb: 'Detalle',
      breadcrumbParam: {
        param: 'eventId',
        label: 'Evento',
      },
      breadcrumbParent: [
        {
          label: 'Eventos',
        },
        {
          label: 'Eventos de conciliación',
          url: NAVIGATION.admin.reconciliationEvents,
        },
      ],
    },
  },
];
