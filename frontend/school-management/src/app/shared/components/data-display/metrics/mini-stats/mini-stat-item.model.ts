export interface MiniStatItem {
  label: string;
  value: string | number;
  type?: 'warning' | 'success' | 'danger' | 'primary' | 'default';
}
