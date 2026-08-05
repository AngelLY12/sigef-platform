export type KpiBreakdownType = 'warning' | 'success' | 'danger' | 'primary' | 'default';

export interface KpiBreakdownItem {
  label: string;
  value: string | number;
  type?: KpiBreakdownType;
}
