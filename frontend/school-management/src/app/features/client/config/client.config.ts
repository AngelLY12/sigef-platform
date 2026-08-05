import { FolderTab } from '../../../shared/components/navigation/folder-tabs/folder-tabs-config.model';

export const DASHBOARD_HISTORY_COLUMS = [
  { key: 'concept', label: 'Concepto' },
  { key: 'amount', label: 'Monto' },
  { key: 'amount_received', label: 'Recibido' },
  {
    key: 'status',
    label: 'Estado',
    badgeType: (value: string) => {
      switch (value) {
        case 'paid':
          return 'success';
        case 'pending':
          return 'warning';
        case 'overdue':
          return 'danger';
        default:
          return 'default';
      }
    },
  },
  { key: 'date', label: 'Fecha' },
];

export const PAYMENT_DETAILS_TABS: FolderTab[] = [
  {
    id: 'summary',
    label: 'Resumen',
    icon: 'receipt_long',
  },
  {
    id: 'method',
    label: 'Método de pago',
    icon: 'credit_card',
  },
  {
    id: 'actions',
    label: 'Acciones',
    icon: 'settings',
  },
];

export const PENDING_CONCEPTS_TABS: FolderTab[] = [
    {
      id: 'pending',
      label: 'Pendientes',
      icon: 'pending',
    },
    {
      id: 'overdue',
      label: 'Vencidos',
      icon: 'pending',
    },
  ];

