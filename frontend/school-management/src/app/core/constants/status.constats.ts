import { PaymentConceptStatus } from "../models/enums/payment-concepts-status.enum";
import { PaymentStatus } from "../models/enums/payment-status.enum";
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

export const PAYMENT_STATUS_LABELS: Record<PaymentStatus,string> = {
  [PaymentStatus.UNPAID]: 'No pagado',
  [PaymentStatus.UNDERPAID]: 'Pago incompleto',
  [PaymentStatus.SUCCEEDED]: 'Completado',
  [PaymentStatus.REQUIRES_ACTION]: 'Acción requerida',
  [PaymentStatus.PENDING]: 'Pago pendiente',
  [PaymentStatus.PAID]: 'Pagado',
  [PaymentStatus.OVERPAID]: 'Pagado en exceso',
  [PaymentStatus.FAILED]: 'Pago fallido'

}
