import { PaymentConceptStatus } from "../models/enums/payment-concepts-status.enum";
import { Status } from "../models/enums/status.enum";

export const USER_STATUS_LABELS: Record<Status, string> = {
  [Status.ACTIVO]: 'Activo',
  [Status.BAJA_TEMPORAL]: 'Baja temporal',
  [Status.BAJA]: 'Baja',
  [Status.ELIMINADO]: 'Eliminado',
};



export const PAYMENT_CONCEPT_STATUS_LABELS: Record<PaymentConceptStatus, string> = {
  [PaymentConceptStatus.ACTIVO]: 'Activo',
  [PaymentConceptStatus.FINALIZADO]: 'Finalizado',
  [PaymentConceptStatus.DESACTIVADO]: 'Desactivado',
  [PaymentConceptStatus.ELIMINADO]: 'Eliminado',
};
