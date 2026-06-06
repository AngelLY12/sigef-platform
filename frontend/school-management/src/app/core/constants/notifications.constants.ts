import { NotificationConceptAction } from "../models/enums/notification-concept-action.enum"
import { NotificationConceptPriority } from "../models/enums/notification-concept-priority.enum";

export const NOTIFICATION_CONCEPT_ACTION_LABELS: Record<NotificationConceptAction, string> = {
  [NotificationConceptAction.APPLIES_TO_CHANGED]: 'Alcance modificado',
  [NotificationConceptAction.EXCEPTIONS_UPDATE]: 'Excepciones actualizadas',
  [NotificationConceptAction.RELATION_UPDATE]: 'Relaciones actualizadas',
  [NotificationConceptAction.RELATION_REMOVED]: 'Relación eliminada',
  [NotificationConceptAction.CREATED_CONCEPT]: 'Concepto creado',
  [NotificationConceptAction.FIELD_UPDATE]: 'Campos actualizados',
  [NotificationConceptAction.STATUS_UPDATE]: 'Estado actualizado',
};

export const NOTIFICATION_PRIORITY_LABELS: Record<NotificationConceptPriority, string> = {
  [NotificationConceptPriority.HIGH]: 'Alta prioridad',
  [NotificationConceptPriority.MEDIUM]: 'Prioridad media',
  [NotificationConceptPriority.LOW]: 'Baja prioridad',
};
