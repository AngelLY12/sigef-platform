interface SideBarItemBase {
  icon: string;
  label: string;
  key: string;
  badge?: number | boolean;
  badgeColor?: 'primary' | 'success' | 'warning' | 'error';
}

export interface SideBarMenuItem extends SideBarItemBase {
  type:'item';
  route: string;
  exact?: boolean;
}

export interface SideBarMenuGroup extends SideBarItemBase {
  type:'group';
  children: SideBarItem[];
}
export type SideBarItemType = 'item' | 'group';
export type SideBarItem = SideBarMenuItem | SideBarMenuGroup;
