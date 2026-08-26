import { NAVIGATION } from '../../core/navigation/navigation.config';
import { SideBarItem } from '../../shared/components/navigation/sidebar/sidebar-item.model';

export const ADMIN_MENU: SideBarItem[] = [
  {
    label: 'Dashboard',
    icon: 'dashboard',
    route: NAVIGATION.admin.dashboard,
    key: 'dashboard',
    type: 'item',
  },
  {
    label: 'Usuarios',
    icon: 'people',
    route: NAVIGATION.admin.users,
    key: 'users',
    type: 'item',
  },
  {
    label: 'Importar datos',
    icon: 'bar_chart',
    route: NAVIGATION.admin.import,
    key: 'import',
    type: 'item',
  },
  {
    label: 'Eventos',
    icon: 'history',
    key: 'event',
    type: 'group',
    children: [
      {
        label: 'Eventos de correo',
        icon: 'mail',
        route: NAVIGATION.admin.emailEvents,
        key: 'email-events',
        type: 'item',
      },
      {
        label: 'Eventos de pago',
        icon: 'payments',
        route: NAVIGATION.admin.paymentEvents,
        key: 'payment-events',
        type: 'item',
      },
      {
        label: 'Eventos de reconciliación',
        icon: 'sync_alt',
        route: NAVIGATION.admin.reconciliationEvents,
        key: 'reconciliation-events',
        type: 'item',
      },
    ],
  },
];

export const CLIENT_MENU: SideBarItem[] = [
  {
    label: 'Dashboard',
    icon: 'dashboard',
    route: NAVIGATION.client.dashboard,
    key: 'dashboard',
    type: 'item',
  },
  {
    label: 'Conceptos de pago',
    icon: 'receipt_long',
    route: NAVIGATION.client.concepts,
    key: 'pending_concepts',
    type: 'item',
  },
  {
    label: 'Tarjetas de pago',
    icon: 'card_membership',
    route: NAVIGATION.client.cards,
    key: 'cards',
    type: 'item',
  },
  {
    label: 'Historial de pago',
    icon: 'payments',
    route: NAVIGATION.client.paymentHistory,
    key: 'payment_history',
    type: 'item',
  },
];

export const FINANCIAL_MENU: SideBarItem[] = [
  {
    label: 'Dashboard',
    icon: 'dashboard',
    route: NAVIGATION.financial.dashboard,
    key: 'dashboard',
    type: 'item',
  },
  {
    label: 'Conceptos de pago',
    icon: 'receipt_long',
    route: NAVIGATION.financial.concepts,
    key: 'concepts',
    type: 'item',
  },
  {
    label: 'Pagos',
    icon: 'payments',
    route: NAVIGATION.financial.payments,
    key: 'payments',
    type: 'item',
  },
  {
    label: 'Adeudos',
    icon: 'request_quote ',
    route: NAVIGATION.financial.debts,
    key: 'debts',
    type: 'item',
  },
];

export const COMMON_MENU: SideBarItem[] = [
  {
    label: 'Notificaciones',
    icon: 'notifications',
    route: NAVIGATION.notifications.all,
    key: 'notifications',
    type: 'item',
  },
];

export const STUDENT_MENU: SideBarItem[] = [
  {
    label: 'Padres o tutores',
    icon: 'supervisor_account',
    route: NAVIGATION.client.parents,
    key: 'parents',
    type: 'item',
  },
];
