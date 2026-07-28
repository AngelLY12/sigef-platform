import { FolderTab } from '../../../core/models/domain/folder-tabs-config.model';
import { PaymentConceptStatus } from '../../../core/models/enums/payment-concepts-status.enum';

export const CONCEPT_LIST_TABS: FolderTab[] = [
  {
    id: 'all',
    label: 'Todos',
    icon: 'receipt_long',
  },
  {
    id: PaymentConceptStatus.ACTIVO,
    label: 'Activos',
    icon: 'check_circle',
  },
  {
    id: PaymentConceptStatus.DESACTIVADO,
    label: 'Inactivos',
    icon: 'block',
  },
  {
    id: PaymentConceptStatus.FINALIZADO,
    label: 'Finalizados',
    icon: 'flag',
  },
  {
    id: PaymentConceptStatus.ELIMINADO,
    label: 'Eliminados',
    icon: 'delete',
  },
];

export const DEBTS_LIST_TABS: FolderTab[] = [
  {
    id: 'all',
    label: 'Adeudos',
    icon: 'receipt_long'
  }
];
