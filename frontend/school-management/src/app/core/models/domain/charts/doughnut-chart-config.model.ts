export interface DoughnutChartConfig {
  labels: string[];
  datasets: DoghnutChartDataset[];
}

export interface DoghnutChartDataset {
  label?: string;
  data: number[];
  colors?: string[];
}
