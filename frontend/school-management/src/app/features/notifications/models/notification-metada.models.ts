import { NotificationConceptAction } from "../../../core/models/enums/notification-concept-action.enum";
import { NotificationConceptPriority } from "../../../core/models/enums/notification-concept-priority.enum";

export type NotificationMetadataModels =
  | ImportNotificationMetadata
  | InvitationNotificationMetadata
  | PaymentConceptChangedMetadata
  | PaymentConceptStatusChangedMetadata
  | PromotionNotificationMetadata
  | RelationNotificationMetadata;

export interface ImportNotificationMetadata {
  error?: string;
  details?: string;
}

export interface InvitationNotificationMetadata {
  student_name?: string;
  parent_name?: string;
}

export interface PaymentConceptChangedMetadata {
  concept_name?: string;
  changes: string[] | [];
  action: NotificationConceptAction;
  timestamp?: string;
  priority: NotificationConceptPriority;
}

export interface PaymentConceptStatusChangedMetadata {
  concept_name?: string;
  old_status?: string;
  new_status?: string;
  amount?: string;
  status_transition?: string;
  priority: NotificationConceptPriority;
  timestamp?: string;
}

export interface PromotionNotificationMetadata {
  promoted_count?: number;
  deactivated?: number;
  error?: string;
}

export interface RelationNotificationMetadata {
  student_name: string;
  parent_name: string;
}
