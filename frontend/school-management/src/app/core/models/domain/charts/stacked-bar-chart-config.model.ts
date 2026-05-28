export interface StackedBarDataset {
  label: string;
  data: number[];
  color?: string;
}

export interface StackedBarChartConfig {
  labels: string[];
  datasets: StackedBarDataset[];
}
