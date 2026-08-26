import { EventDetailsHeaderData } from './../components/events/event-details-header/event-details-header.model';
import { FolderTab } from '../../../shared/components/navigation/folder-tabs/folder-tabs-config.model';
import { Status } from '../../../core/models/enums/status.enum';
import {
  EmailEventStatus,
  EmailEventStatusLabels,
} from '../models/request/events/email/email-event-status.enum';
import { TableColumn } from '../../../shared/components/data-display/tables/table/table-column.model';
import { PaymentEventType } from '../models/request/events/payment/payment-event-type.enum';
import { ReconciliationEventStatus, ReconciliationEventStatusLabels } from '../models/request/events/reconciliation/reconciliation-event-status.enum';

export const USER_DETAILS_TABS: FolderTab[] = [
  {
    id: 'general',
    label: 'Información',
    icon: 'account_circle',
  },
  {
    id: 'address',
    label: 'Dirección',
    icon: 'home',
  },
  {
    id: 'roles',
    label: 'Roles',
    icon: 'badge',
  },
  {
    id: 'permissions',
    label: 'Permisos',
    icon: 'security',
  },
  {
    id: 'academic',
    label: 'Académico',
    icon: 'school',
  },
];

export const USER_MANAGEMENT_TABS: FolderTab[] = [
  {
    id: '',
    label: 'Todos',
    icon: 'groups',
  },
  {
    id: Status.ACTIVO,
    label: 'Activos',
    icon: 'check_circle',
  },
  {
    id: Status.BAJA,
    label: 'Baja',
    icon: 'block',
  },
  {
    id: Status.BAJA_TEMPORAL,
    label: 'Baja temporal',
    icon: 'schedule',
  },
  {
    id: Status.ELIMINADO,
    label: 'Eliminados',
    icon: 'delete',
  },
];

export const EMAIL_EVENT_TABS: FolderTab[] = [
  {
    id: '',
    label: 'Todos',
    icon: 'mail',
  },
  {
    id: EmailEventStatus.PENDING,
    label: EmailEventStatusLabels[EmailEventStatus.PENDING],
    icon: 'schedule',
  },
  {
    id: EmailEventStatus.SENT,
    label: EmailEventStatusLabels[EmailEventStatus.SENT],
    icon: 'send',
  },
  {
    id: EmailEventStatus.DELIVERED,
    label: EmailEventStatusLabels[EmailEventStatus.DELIVERED],
    icon: 'mark_email_read',
  },
  {
    id: EmailEventStatus.FAILED,
    label: EmailEventStatusLabels[EmailEventStatus.FAILED],
    icon: 'error',
  },
];

export const EMAIL_EVENTS_COLUMNS: TableColumn[] = [
  { key: 'createdAt', label: 'Fecha' },
  { key: 'id', label: 'ID' },
  { key: 'userId', label: 'ID de usuario' },
  { key: 'userName', label: 'Usuario' },
  { key: 'recipientEmail', label: 'Correo de usuario' },
  { key: 'eventType', label: 'Tipo de evento' },
  { key: 'status', label: 'Estatus' },
];

export type PaymentEventTab = 'payment' | 'session' | 'charge';

export const PAYMENT_EVENT_TABS: FolderTab[] = [
  {
    id: '',
    label: 'Todos',
    icon: 'payments',
  },
  {
    id: 'payment',
    label: 'Pagos',
    icon: 'payment',
  },
  {
    id: 'session',
    label: 'Sesiones',
    icon: 'receipt_long',
  },
  {
    id: 'charge',
    label: 'Cargos',
    icon: 'credit_card',
  },
];

export const PAYMENT_EVENT_TYPES_BY_TAB: Record<PaymentEventTab, PaymentEventType[]> = {
  payment: [
    PaymentEventType.WEBHOOK_PAYMENT_INTENT_SUCCEEDED,
    PaymentEventType.WEBHOOK_PAYMENT_FAILED,
    PaymentEventType.WEBHOOK_PAYMENT_REQUIRES_ACTION,
    PaymentEventType.WEBHOOK_PAYMENT_CANCELLED,
  ],

  session: [
    PaymentEventType.WEBHOOK_SESSION_EXPIRED,
    PaymentEventType.WEBHOOK_SESSION_COMPLETED,
    PaymentEventType.WEBHOOK_SESSION_ASYNC_COMPLETED,
  ],

  charge: [
    PaymentEventType.WEBHOOK_CHARGE_SUCCEEDED,
  ],
};

export const PAYMENT_EVENTS_COLUMNS: TableColumn[] = [
  { key: 'createdAt', label: 'Fecha' },
  { key: 'id', label: 'ID' },
  { key: 'paymentId', label: 'ID de pago' },
  { key: 'conceptName', label: 'Concepto' },
  { key: 'processed', label: 'Procesado', format: (value: boolean) => value ? 'Sí' : 'No' },
  { key: 'eventType', label: 'Tipo de evento' },
];


export const RECONCILIATION_EVENT_TABS: FolderTab[] = [
  {
    id: '',
    label: 'Todos',
    icon: 'sync_alt',
  },
  {
    id: ReconciliationEventStatus.PENDING,
    label: ReconciliationEventStatusLabels[ReconciliationEventStatus.PENDING],
    icon: 'schedule',
  },
  {
    id: ReconciliationEventStatus.COMPLETED,
    label: ReconciliationEventStatusLabels[ReconciliationEventStatus.COMPLETED],
    icon: 'check_circle',
  },
  {
    id: ReconciliationEventStatus.FAILED,
    label: ReconciliationEventStatusLabels[ReconciliationEventStatus.FAILED],
    icon: 'error',
  },
];

export const RECONCILIATION_EVENTS_COLUMNS: TableColumn[] = [
  { key: 'createdAt', label: 'Fecha' },
  { key: 'id', label: 'ID' },
  { key: 'paymentId', label: 'ID de pago' },
  { key: 'conceptName', label: 'Concepto' },
  { key: 'sourceId', label: 'ID de fuente' },
  { key: 'sourceType', label: 'Tipo de fuente' },
  { key: 'status', label: 'Estatus'},

];
