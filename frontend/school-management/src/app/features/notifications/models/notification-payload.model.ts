import { NotificationSeverity } from "../../../core/models/enums/notification-severity.enum";
import { NotificationType } from "../../../core/models/enums/notification-type.enum";
import { NotificationMetadataModels } from "./notification-metada.models";

export interface NotificationPayload {
  type: NotificationType;
  title: string;
  message: string;
  severity: NotificationSeverity;
  metadata: NotificationMetadataModels;
}
