export interface KpiBreakdownItem {
  label: string;
  value: string | number;
  type?: 'warning' | 'success' | 'danger' | 'primary' | 'default';
}
