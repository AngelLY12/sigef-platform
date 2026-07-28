import { InfoCardItemConfig } from '../../../core/models/domain/cards/info-card-item-config.model';
import { Status } from '../../../core/models/enums/status.enum';
import { AlertItem } from '../../../shared/components/feedback/alerts-list/alerts-list.component';
import { UserProfile } from '../models/user-profile.model';

export function getContactItems(
  profile: UserProfile | null,
): InfoCardItemConfig[] {
  return [
    {
      icon: 'email',
      label: 'Correo electrónico',
      value: profile?.email ?? 'No disponible',
    },
    {
      icon: 'phone',
      label: 'Teléfono',
      value: profile?.phone_number ?? 'No disponible',
    },
  ];
}

export function getPersonalItems(
  profile: UserProfile | null,
): InfoCardItemConfig[] {
  return [
    {
      icon: 'fingerprint',
      label: 'CURP',
      value: profile?.curp || 'No disponible',
    },
    {
      icon: 'date_range',
      label: 'Fecha de nacimiento',
      value: profile?.birthdate || 'No disponible',
    },
    {
      icon: 'wc',
      label: 'Género',
      value: profile?.gender || 'No disponible',
    },
    {
      icon: 'opacity',
      label: 'Tipo de sangre',
      value: profile?.blood_type || 'No disponible',
    },
  ];
}

export function getAddressItems(
  profile: UserProfile | null,
): InfoCardItemConfig[] {
  return [
    {
      icon: 'markunread_mailbox',
      label: 'Código Postal',
      value: profile?.address?.cp || 'No disponible',
    },
    {
      icon: 'map',
      label: 'Estado',
      value: profile?.address?.state || 'No disponible',
    },
    {
      icon: 'location_city',
      label: 'Municipio',
      value: profile?.address?.city || 'No disponible',
    },
    {
      icon: 'apartment',
      label: 'Colonia',
      value: profile?.address?.neighborhood || 'No disponible',
    },
    {
      icon: 'route',
      label: 'Calle',
      value: profile?.address?.street || 'No disponible',
    },
    {
      icon: 'home',
      label: 'Número',
      value: profile?.address?.number || 'No disponible',
    },
  ];
}

export function getAccountItems(
  profile: UserProfile | null,
): InfoCardItemConfig[] {
  return [
    {
      icon: 'info',
      label: 'Estatus',
      value: profile?.status || 'No disponible',
    },
    {
      icon: 'event',
      label: 'Fecha de registro',
      value: profile?.registration_date || 'No disponible',
    },
    {
      icon: 'verified',
      label: 'Verificación de Email',
      value: profile?.emailVerifiedAt || 'No disponible',
    },
    {
      icon: 'credit_card',
      label: 'ID Stripe',
      value: profile?.stripe_customer_id || 'No disponible',
    },
  ];
}

export function getAccountAlerts(profile: UserProfile | null): AlertItem[] {
  if (profile?.emailVerifiedAt && profile?.status === Status.ACTIVO) return [];
  const alerts = [];
  if (!profile?.emailVerifiedAt) {
    alerts.push({
      icon: 'verified',
      title: 'Usuario sin verificar',
      count: null,
    });
  }
  if (profile?.status !== Status.ACTIVO) {
    alerts.push({
      icon: 'block',
      title: 'Usuario inactivo',
      count: null,
    });
  }

  return alerts;
}
