export interface SideBarItem {
  icon: string;
  label: string;
  route: string;
  key: string;
  exact?: boolean;
  badge?: number|boolean;
  badgeColor?: 'primary' | 'success' | 'warning' | 'error';
  children?: SideBarItem[];
}
