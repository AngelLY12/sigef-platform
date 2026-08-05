export interface SelectorItem<T = unknown> {
  id: number | string;
  icon: string;
  title: string;
  description?: string;
  colorClass?: string;
  data: T;
}
