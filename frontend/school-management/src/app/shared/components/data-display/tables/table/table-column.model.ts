export type TableType = 'text' | 'badge' | 'date' | 'number';
export type TableBadgeType<T> = 'success' | 'warning' | 'error' | 'info' | ((value: any, row: T) => string);

export interface TableColumn<T = any> {
  key: keyof T | string;
  label: string;
  type?: TableType;
  badgeType?: TableBadgeType<T>;
  format?: (value: any, row: T) => string;
  class?: string;
}
