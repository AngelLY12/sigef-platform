export interface MenuItem {
  icon: string;
  label: string;
  route: string;
  key: string;
  exact?: boolean;
  badge?: number|boolean;
  badgeColor?: 'primary' | 'success' | 'warning' | 'error';
  children?: MenuItem[];
}
