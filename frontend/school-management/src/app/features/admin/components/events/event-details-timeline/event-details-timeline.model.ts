import { statusType } from "../../../../../core/models/types/status-type.type";

export interface EventTimelineItem {
  label: string;
  date: string | null;
  status?: TimelineStatus;
  badge?: TimelineBadge;
  description?: string;
}

export interface TimelineBadge {
  type: statusType;
  text: string;
}

export type TimelineStatus = 'pending' | 'success' | 'error';
