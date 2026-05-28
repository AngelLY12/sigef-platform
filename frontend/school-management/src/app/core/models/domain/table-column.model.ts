export interface TableColumn<T = any> {
  key: keyof T | string;
  label: string;
  type?: 'text' | 'badge' | 'date' | 'number';
  badgeType?: 'success' | 'warning' | 'error' | 'info' | ((value: any, row: T) => string);
  format?: (value: any, row: T) => string;
  class?: string;
}
