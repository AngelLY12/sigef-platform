export interface EventDetailsHeaderData {
  id: number | string | null;
  status: string | null;
  statusLabel: string | null;
  statusType: MetadataBadgeType;
  icon: string;
  recipient?: string | null;
  context: string[];
}

export type MetadataBadgeType =
  | 'success'
  | 'error'
  | 'warning'
  | 'info';
