import { NAVIGATION } from '../../core/navigation/navigation.config';
import { SideBarItem } from '../../shared/components/navigation/sidebar/sidebar-item.model';

export const ADMIN_MENU: SideBarItem[] = [
  {
    label: 'Dashboard',
    icon: 'dashboard',
    route: NAVIGATION.admin.dashboard,
    key: 'dashboard',
  },
  {
    label: 'Usuarios',
    icon: 'people',
    route: NAVIGATION.admin.users,
    key: 'users',
  },
  {
    label: 'Importar datos',
    icon: 'bar_chart',
    route: NAVIGATION.admin.import,
    key: 'import',
  },
];

export const CLIENT_MENU: SideBarItem[] = [
  {
    label: 'Dashboard',
    icon: 'dashboard',
    route: NAVIGATION.client.dashboard,
    key: 'dashboard',
  },
  {
    label: 'Conceptos de pago',
    icon: 'receipt_long',
    route: NAVIGATION.client.concepts,
    key: 'pending_concepts',
  },
  {
    label: 'Tarjetas de pago',
    icon: 'card_membership',
    route: NAVIGATION.client.cards,
    key: 'cards',
  },
  {
    label: 'Historial de pago',
    icon: 'payments',
    route: NAVIGATION.client.paymentHistory,
    key: 'payment_history',
  },
];

export const FINANCIAL_MENU: SideBarItem[] = [
  {
    label: 'Dashboard',
    icon: 'dashboard',
    route: NAVIGATION.financial.dashboard,
    key: 'dashboard',
  },
  {
    label: 'Conceptos de pago',
    icon: 'receipt_long',
    route: NAVIGATION.financial.concepts,
    key: 'concepts',
  },
  {
    label: 'Pagos',
    icon: 'payments',
    route: NAVIGATION.financial.payments,
    key: 'payments',
  },
  {
    label: 'Adeudos',
    icon: 'request_quote ',
    route: NAVIGATION.financial.debts,
    key: 'debts',
  },
];

export const COMMON_MENU: SideBarItem[] = [
  {
    label: 'Notificaciones',
    icon: 'notifications',
    route: NAVIGATION.notifications.all,
    key: 'notifications',
  },
];

export const STUDENT_MENU: SideBarItem[] = [
  {
    label: 'Padres o tutores',
    icon: 'supervisor_account',
    route: NAVIGATION.client.parents,
    key: 'parents',
  },
];


