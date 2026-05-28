interface BaseAppliesConfig {
  allowExceptions?: boolean;
}

interface MultiSelectConfig extends BaseAppliesConfig {
  type: 'multiselect';
  label: string;
  controlName: string;
  options: any[];
}

interface SearchConfig extends BaseAppliesConfig {
  type: 'search';
  label: string;
  controlName: string;
  options: any[];
}

interface CareerSemesterConfig extends BaseAppliesConfig {
  type: 'career-semester';
  label: string;
}

interface InfoConfig extends BaseAppliesConfig {
  type: 'info';
  message: string;
}

export type AppliesToConfig =
  | MultiSelectConfig
  | SearchConfig
  | CareerSemesterConfig
  | InfoConfig;
