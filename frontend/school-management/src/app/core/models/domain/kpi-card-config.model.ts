import { KpiIconType } from "../../../shared/components/data-display/kpi-card/kpi-card.component";

export interface KpiCardConfig {
  icon: string;
  iconType: KpiIconType;
  label: string;
  value: any;
  trend?: { icon: string; text: string } | null;
  percentage?: number;
  subtext?: string;
  size?: 'normal' | 'small';
}
