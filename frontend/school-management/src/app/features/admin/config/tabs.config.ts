import { FolderTab } from '../../../shared/components/navigation/folder-tabs/folder-tabs-config.model';
import { Status } from '../../../core/models/enums/status.enum';

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
