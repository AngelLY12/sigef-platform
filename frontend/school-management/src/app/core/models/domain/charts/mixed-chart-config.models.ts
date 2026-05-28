export interface MixedChartDataset {
  type: 'bar' | 'line';
  label: string;
  data: number[];
  color?: string;
}

export interface MixedChartConfig {
  labels: string[];
  datasets: MixedChartDataset[];
}
