export interface LineChartConfig {
  labels: string[];
  datasets: LineChartDataset[];
}

export interface LineChartDataset {
  label?: string;
  data: number[];
  color?: string;
  fill?: boolean;
}
