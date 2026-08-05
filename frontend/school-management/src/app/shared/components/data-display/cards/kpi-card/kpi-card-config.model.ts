
export type KpiIconType = 'total' | 'active' | 'students' | 'alerts' | 'inactive' | 'growth' | 'roles' | 'academic';

export type KpiSize = 'normal' | 'small';

export interface KpiTrend {
  icon: string;
  text: string;
}

export interface KpiCardConfig {
  icon: string;
  iconType: KpiIconType;
  label: string;
  value: any;
  trend?: KpiTrend | null;
  percentage?: number;
  subtext?: string;
  size?: KpiSize;
}

